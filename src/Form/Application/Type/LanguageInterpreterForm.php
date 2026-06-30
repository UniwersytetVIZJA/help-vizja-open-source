<?php

declare(strict_types=1);

namespace App\Form\Application\Type;

use App\Core\DictionaryItem\DictionaryItemRepository;
use App\Database\Entity\Application\LanguageInterpreter;
use App\Database\Entity\Dictionary\Item;
use App\Enum\Dictionary\DictionaryNameEnum;
use App\Form\Application\Type\Attachment\ApplicationFileType;
use App\Form\Sanitazer;
use Doctrine\ORM\EntityManagerInterface;
use Exercise\HTMLPurifierBundle\HTMLPurifiersRegistryInterface;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_filter;
use function array_keys;
use function array_values;
use function asort;
use function class_exists;
use function is_array;
use function uasort;
use const SORT_NATURAL;

/**
 * Class LanguageInterpreterForm
 * @package App\Form\Application\Type
 */
class LanguageInterpreterForm extends AbstractType
{
    /**
     * LanguageInterpreterForm constructor
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface $translator
     * @param HTMLPurifiersRegistryInterface $purifier
     * @param Sanitazer $sanitazer
     */
    public function __construct(
        private(set) readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly HTMLPurifiersRegistryInterface $purifier, private readonly Sanitazer $sanitazer
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
            ->add('justification', TextareaType::class, [
                'label' => $this->translator->trans('Opisz, dlaczego potrzebujesz usług tłumacza (opis musi mieć przynajmniej 150 znaków)') . '*',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => $this->translator->trans('Musisz opisać dlaczego potrzebujesz usług tłumacza')]),
                    new Length([
                        'min' => 150,
                        'max' => 1000,
                        'minMessage' => 'Uzasadnienie jest obowiązkowe i musisz napisać conajmniej {{ limit }} znaków',
                        'maxMessage' => 'Możesz napisać maksymalnie {{ limit }} znaków w uzasadnieniu',
                    ]),
                ],
            ])
            ->add('preferences', EntityType::class, [
                'label' => $this->translator->trans('Preferuję tłumacza posługującego się:'),
                'placeholder' => false,
                'required' => false,
                'class' => Item::class,
                'query_builder' => fn(DictionaryItemRepository $r) => $r->findAllByDictionaryNameQueryBuilder(DictionaryNameEnum::PREFERENCJE_TLUMACZA_MIGOWEGO),
                'choice_label' => 'value',
                'expanded' => true,
                'multiple' => false,
                'by_reference' => true,
            ])
            ->add('anotherPreferences', TextareaType::class, [
                'label' => $this->translator->trans('Inne preferencje spoza listy'),
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => $this->translator->trans('Maksymalna liczba znaków to {{ limit }}'),
                    ]),
                ],
            ])
            ->add('request', EntityType::class, [
                'label' => $this->translator->trans('Kiedy chcesz skorzystać z usług tłumacza?') . '*',
                'required' => true,
                'expanded' => true,
                'multiple' => true,
                'placeholder' => $this->translator->trans('Wybierz, kiedy chcesz skorzystać z usług tłumacza'),
                'class' => Item::class,
                'query_builder' => fn(DictionaryItemRepository $repository) => $repository->findAllByDictionaryNameQueryBuilder(DictionaryNameEnum::PRZYZNANIE_KIEDY),
                'choice_label' => 'value',
            ])
            ->add('statement', CollectionType::class, [
                'label' => $this->translator->trans('Wgraj orzeczenie lub inny dokument potwierdzający trudności') . '*',
                'entry_type' => ApplicationFileType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => true,
                'required' => true,
                'prototype' => true,
                'mapped' => false,
                'error_bubbling' => false,
                'constraints' => [
                    new Count([
                        'min' => $this->minFiles($options, 'statement'),
                        'minMessage' => $this->translator->trans('Brakuje załącznika. Wgraj orzeczenie o niepełnosprawności lub inny dokument potwierdzający trudności'),
                    ]),
                    new Valid(),
                ],
            ])
            ->add('schedule', CollectionType::class, [
                'label' => $this->translator->trans('Wgraj harmonogram zajęć z zaznaczeniem, na których zajęciach tłumacz jest niezbędny') . '*',
                'entry_type' => ApplicationFileType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => true,
                'required' => true,
                'prototype' => true,
                'mapped' => false,
                'error_bubbling' => false,
                'constraints' => [
                    new Count([
                        'min' => $this->minFiles($options, 'schedule'),
                        'minMessage' => $this->translator->trans('Brakuje załącznika. Wgraj harmonogram zajęć z zaznaczeniem, na których zajęciach tłumacz jest niezbędny'),
                    ]),
                    new Valid(),
                ],
            ])
            ->add('statute', CheckboxType::class, [
                'label' => $this->translator->trans(
                    'Zapoznałem/am się z Regulaminem współpracy z tłumaczem języka migowego.'
                ),
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'aria-required' => 'true',
                ],
                'constraints' => [
                    new IsTrue([
                        'message' => $this->translator->trans('Musisz potwierdzić regulamin, aby przejść dalej'),
                    ]),
                ],
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $data = $this->sanitazer->sanitaze($data, [
                'justification' => ['strip_tags' => true],
                'anotherPreferences' => ['strip_tags' => true],
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
            'data_class' => LanguageInterpreter::class,
            'adaptation_required' => true,
            'files_counts' => [],
        ]);
    }
}
