<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Core\DictionaryItem\DictionaryItemRepository;
use App\Database\Entity\Application;
use App\Database\Entity\Dictionary\Item;
use App\Enum\Dictionary\DictionaryNameEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ApplicationTypeForm
 * @package App\Form\Application
 */
class ApplicationTypeForm extends AbstractType
{
    /**
     * ApplicationTypeForm constructor
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private(set) readonly EntityManagerInterface $entityManager, private readonly RequestStack $requestStack, private readonly TranslatorInterface $translator
    ) {}

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', EntityType::class, [
                'choice_label' => function (Item $item) {
                    $locale = $this->requestStack->getCurrentRequest()?->getLocale();

                    return $locale === 'pl' ? $item->value : $item->valueEnglish;
                },
                'choice_attr' => function (Item $item) {
                    $locale = $this->requestStack->getCurrentRequest()?->getLocale();

                    return [
                        'data-value-key' => $locale === 'pl' ? $item->valueKey : $item->valueKeyEng,
                        'data-hidden-value' => $item->hiddenValue,
                    ];
                },

                'label' => $this->translator->trans('Rodzaj wniosku'),
                'class' => Item::class,
                'expanded' => true,
                'required' => true,
                'error_bubbling' => false,
                'query_builder' => fn(DictionaryItemRepository $repository) => $repository->findAllByDictionaryNameQueryBuilder(DictionaryNameEnum::TYPY_WNIOSKOW),
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Wybierz rodzaj wniosku'),
                    ]),
                ],
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class' => Application::class,
            'allow_extra_fields' => true,
            'empty_data' => function (FormInterface $form, $data) {
                return new class() extends Application {};
            },
            'dictionary_name' => DictionaryNameEnum::TYPY_WNIOSKOW,
        ]);
    }
}
