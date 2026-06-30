<?php

namespace App\Form\StudentProfile;

use App\Database\Entity\Student;
use App\Enum\User\NotificationLanguageEnum;
use App\Form\Sanitazer;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_filter;
use function array_keys;
use function array_values;
use function asort;
use function class_exists;
use function is_array;
use function uasort;
use const SORT_NATURAL;

class UpdateDataForm extends AbstractType
{
    /**
     * @param TranslatorInterface $translator
     * @param Sanitazer $sanitazer
     */
    public function __construct(private TranslatorInterface $translator, private readonly Sanitazer $sanitazer) {}

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $regions = PhoneNumberUtil::getInstance()->getSupportedRegions();

        $regions = array_values(array_filter($regions, static fn(string $r) => Countries::exists($r)));

        $names = [];
        foreach ($regions as $r) {
            $names[$r] = Countries::getName($r);
        }

        if (class_exists(\Collator::class)) {
            $collator = new \Collator(\Locale::getDefault());
            uasort($names, fn($a, $b) => $collator->compare($a, $b));
        } else {
            asort($names, SORT_NATURAL);
        }

        $builder
            ->add('phone', PhoneNumberType::class, [
                'label' => $this->translator->trans('Numer telefonu'),
                'country_choices' => array_keys($names),
                'country_display_emoji_flag' => true,
                'country_display_type' => 'display_country_full',
                'widget' => PhoneNumberType::WIDGET_COUNTRY_CHOICE,
                'format' => PhoneNumberFormat::INTERNATIONAL,
                'preferred_country_choices' => ['PL'],
                'default_region' => 'PL',
                'required' => false,
            ])
            ->add('notificationLanguage', ChoiceType::class, [
                'label' => $this->translator->trans('Język powiadomień'),
                'choices' => [
                    $this->translator->trans('Polski') => NotificationLanguageEnum::Polski->value,
                    $this->translator->trans('Angielski') => NotificationLanguageEnum::Angielski->value,
                ],
                'data' => NotificationLanguageEnum::Polski->value,
                'placeholder' => false,
                'required' => true,
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $data = $this->sanitazer->sanitaze($data, [
                'phone' => ['strip_tags' => true],
            ]);

            $event->setData($data);
        });
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Student::class,
            'translation_domain' => 'messages',
        ]);
    }
}
