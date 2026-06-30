<?php

namespace App\Form\Registration;

use App\Database\Entity\RegisteredStudent;
use App\Form\Sanitazer;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_filter;
use function array_keys;
use function array_values;
use function asort;
use function class_exists;
use function is_array;
use function uasort;
use const SORT_NATURAL;

class UpdateStudentForm extends AbstractType
{
    public function __construct(private TranslatorInterface $translator, private readonly Sanitazer $sanitazer) {}

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
            ->add('albumNumber', IntegerType::class, [
                'label' => $this->translator->trans('Numer albumu'),
                'required' => false,
                'attr' => ['min' => 0],
            ])
            ->add('phone', PhoneNumberType::class, [
                'label' => $this->translator->trans('Numer telefonu') . '*',
                'country_choices' => array_keys($names),
                'country_display_emoji_flag' => true,
                'country_display_type' => 'display_country_full',
                'widget' => PhoneNumberType::WIDGET_COUNTRY_CHOICE,
                'format' => PhoneNumberFormat::INTERNATIONAL,
                'preferred_country_choices' => ['PL'],
                'default_region' => 'PL',
                'required' => true,
                'invalid_message' => 'Podaj poprawny numer telefonu',
                'constraints' => [
                    new NotBlank(message: $this->translator->trans('Wpisz numer telefonu')),
                    new AssertPhoneNumber(message: 'Podaj poprawny numer telefonu'),
                ],
            ])
            ->add('specialNeeds', TextareaType::class, [
                'label' => $this->translator->trans('Podaj szczególne potrzeby dotyczące organizacji spotkania, jeśli takie posiadasz'),
                'required' => false,
            ])
            ->add('meetingMode', ChoiceType::class, [
                'label' => $this->translator->trans('Wybierz typ spotkania'),
                'choices' => [
                    $this->translator->trans('Spotkanie online (MS Teams)') => 'Spotkanie online',
                    $this->translator->trans('Spotkanie w siedzibie biura (ul. Okopowa 59, Warszawa)') => 'Spotkanie stacjonarne',
                ],
                'expanded' => false,
                'multiple' => false,
                'mapped' => true,
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Wybierz typ spotkania'),
                    ]),
                ],
            ])
            ->add('language', ChoiceType::class, [
                'label' => $this->translator->trans('Wybierz język spotkania'),
                'choices' => [
                    $this->translator->trans('Polski') => 'PL',
                    $this->translator->trans('Angielski') => 'ENG',
                ],
                'expanded' => false,
                'multiple' => false,
                'mapped' => true,
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Wybierz język spotkania'),
                    ]),
                ],
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $data = $this->sanitazer->sanitaze($data, [
                'specialNeeds' => ['strip_tags' => true],
            ]);

            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisteredStudent::class,
        ]);
    }
}
