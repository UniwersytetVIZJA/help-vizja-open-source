<?php

declare(strict_types=1);

namespace App\Form\Application\Type;

use App\Core\DictionaryItem\DictionaryItemRepository;
use App\Database\Entity\Application\EducationalProcess;
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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Range;
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
 * Class EducationalProcessForm
 * @package App\Form\Application\Type
 */
class EducationalProcessForm extends AbstractType
{
    /**
     * EducationalProcessForm constructor
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface $translator
     * @param HTMLPurifiersRegistryInterface $purifier
     * @param Sanitazer $sanitazer
     */
    public function __construct(
        private(set) readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly HTMLPurifiersRegistryInterface $purifier, private readonly Sanitazer $sanitazer,
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
            ->add('adaptations', EntityType::class, [
                'label' => $this->translator->trans('Rodzaj adaptacji') . '*',
                'required' => true,
                'expanded' => true,
                'attr' => [
                    'fieldset' => true,
                ],
                'multiple' => true,
                'class' => Item::class,
                'query_builder' => fn(DictionaryItemRepository $repository) => $repository->findAllByDictionaryNameQueryBuilder(DictionaryNameEnum::RODZAJE_ADAPTACJI),
                'choice_label' => 'value',
                'constraints' => [
                    new Count([
                        'min' => 1,
                        'minMessage' => $this->translator->trans(
                            'Wybierz co najmniej jeden rodzaj adaptacji lub wybierz "inne", jeżeli nie ma twojego na liście'
                        ),
                    ]),
                ],
            ])
            ->add('adaptation_another', TextType::class, [
                'label' => $this->translator->trans('Inny rodzaj adaptacji (jeżeli nie ma na liście)'),
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => $this->translator->trans(
                        'Opisz, jak Twoje niepełnosprawności lub szczególne potrzeby wpływają na proces kształcenia i dlaczego zaznaczyłaś/zaznaczyłeś wybrane adaptacje?*'
                    ) . '<br>' . $this->translator->trans('Opis musi mieć przynajmniej 150 znaków'),
                'label_html' => true,
                'required' => true,
                'constraints' => [
                    new NotBlank(message: $this->translator->trans(
                        'Musisz opisać swoją niepełnosprawność lub szczególne optrzeby i dlaczego zaznaczyłeś wybrane adaptacje'
                    )),
                    new Length(min: 150, max: 1000, minMessage: 'Uzasadnienie jest obowiązkowe i musisz napisać conajmniej {{ limit }} znaków', maxMessage: 'Możesz napisać maksymalnie {{ limit }} znaków w uzasadnieniu'),
                ],
            ])
            ->add('files', CollectionType::class, [
                'label' => $this->translator->trans(
                        'Orzeczenie o niepełnosprawności lub inny dokument potwierdzający trudności'
                    ) . '*',
                'entry_type' => ApplicationFileType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'error_bubbling' => false,
                'mapped' => false,
                'prototype' => true,
                'required' => true,
                'constraints' => [
                    new Count(min: $this->minFiles($options, 'files'), minMessage: $this->translator->trans(
                        'Brakuje załącznika. Wgraj orzeczenie o niepełnosprawności lub inny dokument potwierdzający trudności'
                    )),
                    new Valid(),
                ],
            ])
            ->add('agreementDean', CheckboxType::class, [
                'label' => $this->translator->trans(
                    'Poinformowanie właściwego dziekana o specyfice trudności zdrowotnych mających wpływ na proces kształcenia oraz analizę dokumentów załączonych do wniosku, niezbędnych do podjęcia decyzji dotyczących przyznania adaptacji'
                ),
                'required' => true,
                'mapped' => false,
                'constraints' => [
                    new IsTrue(message: $this->translator->trans('Musisz zaznaczyć tę zgodę')),
                ],
            ])
            ->add('agreementLecturers', CheckboxType::class, [
                'label' => $this->translator->trans(
                    'Poinformowanie wykładowców i pracowników zaangażowanych w obsługę studentów o fakcie wydania mi karty adaptacji'
                ),
                'required' => true,
                'mapped' => false,
                'constraints' => [
                    new IsTrue(message: $this->translator->trans('Musisz zaznaczyć tę zgodę')),
                ],
            ])
            ->add('statute', CheckboxType::class, [
                'label' => $this->translator->trans(
                    'Zapoznałem się z Regulaminem w sprawie dostosowania procesu kształcenia do potrzeb osób ze szczególnymi potrzebami, w tym osób z niepełnosprawnościami wprowadzonym na podstawie Zarządzenia Rektora Uniwersytu VIZJA'
                ),
                'required' => true,
                'mapped' => false,
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
                'adaptation_another' => ['strip_tags' => true],
                'description' => ['strip_tags' => true],
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
            'data_class' => EducationalProcess::class,
            'adaptation_required' => false,
            'files_counts' => [],
        ]);
    }
}
