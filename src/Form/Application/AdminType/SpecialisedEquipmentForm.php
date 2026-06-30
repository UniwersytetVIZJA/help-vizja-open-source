<?php

namespace App\Form\Application\AdminType;

use App\Core\DictionaryItem\DictionaryItemRepository;
use App\Database\Entity\Application\SpecialisedEquipment;
use App\Database\Entity\Dictionary\Item;
use App\Database\Entity\Inventory;
use App\Database\Repository\InventoryRepository;
use App\Enum\Dictionary\DictionaryNameEnum;
use App\Form\Sanitazer;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Length;
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

class SpecialisedEquipmentForm extends AbstractType
{
    /**
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface $translator
     * @param InventoryRepository $inventoryRepository
     * @param Sanitazer $sanitazer
     */
    public function __construct(
        private(set) readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator, private readonly InventoryRepository $inventoryRepository, private readonly Sanitazer $sanitazer, private readonly RequestStack $requestStack,
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
            ->add('equipmentItems', EntityType::class, [
                'label' => $this->translator->trans('Sprzęt do wypożyczenia') . '*',
                'required' => true,
                'class' => Item::class,
                'query_builder' => fn(DictionaryItemRepository $r) => $r->findAllByDictionaryNameQueryBuilder(DictionaryNameEnum::SPRZET),
                'choice_label' => function (Item $item) {
                    $locale = $this->requestStack->getCurrentRequest()?->getLocale();

                    return $locale === 'pl' ? $item->value : $item->valueEnglish;
                },
                'expanded' => true,
                'multiple' => true,
                'mapped' => false,
                'constraints' => [
                    new Count([
                        'min' => 1,
                        'minMessage' => $this->translator->trans('Wybierz sprzęt do wypożyczenia'),
                    ]),
                ],
            ])
            ->add('equipment', EntityType::class, [
                'label' => $this->translator->trans('Typ sprzętu') . '*',
                'class' => Inventory::class,
                'multiple' => true,
                'expanded' => false,
                'by_reference' => false,
                'required' => false,
                'choice_label' => fn(Inventory $inv) => (string)$inv->id,
                'choice_value' => fn(?Inventory $inv) => $inv ? (string)$inv->id : '',
                'constraints' => [
                    new Count([
                        'min' => 1,
                        'minMessage' => $this->translator->trans('Wybierz typ sprzętu'),
                    ]),
                ],
            ])
            ->add('rentStart', DateType::class, [
                'required' => true,
                'input' => 'datetime_immutable',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(message: $this->translator->trans('Wybierz datę rozpoczęcia')),
                ],
            ])
            ->add('rentEnd', DateType::class, [
                'required' => true,
                'input' => 'datetime_immutable',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(message: $this->translator->trans('Wybierz datę zakończenia')),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => $this->translator->trans('Opisz, dlaczego potrzebujesz wypożyczyć sprzęt') . '*',
                'required' => true,
                'label_html' => true,
                'constraints' => [
                    new NotBlank(['message' => $this->translator->trans('Opisz, dlaczego potrzebujesz wypożyczyć sprzęt')]),
                    new Length([
                        'min' => 1,
                        'max' => 1000,
                        'minMessage' => 'Uzasadnienie jest obowiązkowe i musisz napisać conajmniej {{ limit }} znaków',
                        'maxMessage' => 'Możesz napisać maksymalnie {{ limit }} znaków w uzasadnieniu',
                    ]),
                ],
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            /** @var SpecialisedEquipment $data */
            $data = $event->getForm()->getData();
            $submitted = $event->getData() ?? [];

            $start = $data->rentStart ?? null;
            $end = $data->rentEnd ?? null;

            $data->equipment->clear();

            $itemIds = array_map('intval', $submitted['equipmentItems'] ?? []);

            $subtypesPerItem = $submitted['subtype'] ?? [];

            foreach ($itemIds as $itemId) {
                $typeId = isset($subtypesPerItem[(string)$itemId]) ? (int)$subtypesPerItem[(string)$itemId] : null;

                if (!$typeId) {
                    continue;
                }

                $inv = $this->inventoryRepository->findFirstAvailableByItemAndType($itemId, $typeId, $start, $end);

                if ($inv) {
                    $inv->status = 'Zarezerwowany';
                    $data->equipment->add($inv);
                }
            }

            $event->setData($submitted);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $data = $this->sanitazer->sanitaze($data, [
                'description' => ['strip_tags' => true],
            ]);

            $event->setData($data);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            $start = $data->rentStart;
            $end = $data->rentEnd;

            $today = new DateTimeImmutable();

            if (!$start || !$end) {
                return;
            }

            $maxEnd = (clone $start)->add(new DateInterval('P12M'));

            if ($end > $maxEnd) {
                $form->get('rentEnd')->addError(
                    new FormError($this->translator->trans('Data zakończenia nie może być później niż 12 miesięcy po dacie rozpoczęcia.'))
                );
            }

            if ($end < $start) {
                $form->get('rentEnd')->addError(
                    new FormError($this->translator->trans('Data zakończenia nie może być mniejsza niż data rozpoczęcia.'))
                );
            }

            if ($start < $today) {
                $form->get('rentStart')->addError(
                    new FormError($this->translator->trans('Data rozpoczęcia nie może być wcześniejsza niż dzień dzisiejszy.'))
                );
            }
        });
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class' => SpecialisedEquipment::class,
            'adaptation_required' => true,
            'files_counts' => [],
        ]);
    }

    private function minFiles(array $options, string $fieldName): int
    {
        return ($options['files_counts'][$fieldName] ?? 0) > 0 ? 0 : 1;
    }
}
