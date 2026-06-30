<?php

declare(strict_types=1);

namespace App\Form\Application\AdminType;

use App\Core\DictionaryItem\DictionaryItemRepository;
use App\Database\Entity\Application;
use App\Database\Entity\Dictionary\Item;
use App\Enum\Dictionary\DictionaryNameEnum;
use App\Form\Application\AdminType\Attachment\ApplicationFileType;
use App\Form\Sanitazer;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_filter;
use function array_keys;
use function array_values;
use function asort;
use function class_exists;
use function uasort;
use const SORT_NATURAL;

/**
 * Class EducationalProcessForm
 * @package App\Form\Application\Type
 */
class TeachingAssistantForm extends AbstractType
{
    /**
     * EducationalProcessForm constructor
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface $translator
     * @param Sanitazer $sanitazer
     */
    public function __construct(
        private(set) readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator, private readonly Sanitazer $sanitazer, private readonly RequestStack $requestStack,
    ) {}

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
            ->add('assistant', TextType::class, [
                'label' => $this->translator->trans('Jeśli chcesz wskazać kandydatkę/kandydata na asystentkę/asystenta podaj jej/jego imię i nazwisko (opcjonalne)'),
                'required' => false,
            ])
            ->add('preferences', EntityType::class, [
                'label' => $this->translator->trans('Jeśli chcesz, abyśmy sami zaproponowali Ci asystenta dydaktycznego, możesz podzielić się swoimi preferencjami zaznaczając wybrane opcje:'),
                'required' => false,
                'class' => Item::class,
                'query_builder' => fn(DictionaryItemRepository $r) => $r->findAllByDictionaryNameQueryBuilder(DictionaryNameEnum::PREFERENCJE_ASYSTENTA),
                'choice_label' => function (Item $item) {
                    $locale = $this->requestStack->getCurrentRequest()?->getLocale();

                    return $locale === 'pl' ? $item->value : $item->valueEnglish;
                },
                'expanded' => true,
                'multiple' => true,
                'by_reference' => false,
            ])
            ->add('assistantTask', TextareaType::class, [
                'label' => $this->translator->trans('Opisz, dlaczego asystent dydaktyczny jest Ci potrzebny oraz jakie zadania/usługi miałby wykonywać') . '*',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Uzupełnij opis'),
                    ]),
                ],
            ])
            ->add('assistantHours', TextareaType::class, [
                'label' => $this->translator->trans('Szacowana liczba godzin w miesiącu') . '*',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Podaj szacowaną liczbę godzin w miesiącu'),
                    ]),
                ],
            ])
            ->add('decision', CollectionType::class, [
                'label' => $this->translator->trans('Wgraj orzeczenie lub inny dokument potwierdzający specjalne potrzeby') . '*',
                'entry_type' => ApplicationFileType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => true,
                'prototype' => true,
                'required' => true,
                'mapped' => false,
                'error_bubbling' => false,
                'constraints' => [
                    new Count([
                        'min' => $this->minFiles($options, 'decision'),
                        'minMessage' => $this->translator->trans('Brakuje załącznika. Wgraj orzeczenie o niepełnosprawności lub inny dokument potwierdzający trudności'),
                    ]),
                    new Valid(),
                ],
            ])
            ->add('schedule', CollectionType::class, [
                'label' => $this->translator->trans('Wgraj harmonogram zajęć z zaznaczeniem, na których zajęciach asystent jest niezbędny') . '*',
                'entry_type' => ApplicationFileType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => true,
                'prototype' => true,
                'required' => true,
                'mapped' => false,
                'error_bubbling' => false,
                'constraints' => [
                    new Count([
                        'min' => $this->minFiles($options, 'schedule'),
                        'minMessage' => $this->translator->trans('Brakuje załącznika. Wgraj harmonogram zajęć z zaznaczeniem, na których zajęciach asystent jest niezbędny'),
                    ]),
                    new Valid(),
                ],
            ])
            ->add('statute', CheckboxType::class, [
                'label' => $this->translator->trans(
                    'Zapoznałem się z Regulaminem w sprawie dostosowania procesu kształcenia do potrzeb osób ze szczególnymi potrzebami, w tym osób z niepełnosprawnościami wprowadzonym na podstawie Zarządzenia Rektora Uniwersytetu VIZJA'
                ),
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new IsTrue([
                        'message' => $this->translator->trans('Musisz zaznaczyć tę zgodę'),
                    ]),
                ],
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $data = $this->sanitazer->sanitaze($data, [
                'assistantTask' => ['strip_tags' => true],
                'assistant' => ['strip_tags' => true],
                'assistantHours' => ['strip_tags' => true]
            ]);

            $event->setData($data);
        });
    }

    private function minFiles(array $options, string $fieldName): int
    {
        return ($options['files_counts'][$fieldName] ?? 0) > 0 ? 0 : 1;
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class' => Application\TeachingAssistant::class,
            'adaptation_required' => false,
            'files_counts' => [],
        ]);
    }
}
