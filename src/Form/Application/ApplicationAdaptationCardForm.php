<?php

namespace App\Form\Application;

use App\Core\DictionaryItem\DictionaryItemRepository;
use App\Database\Entity\Application;
use App\Database\Entity\Dictionary\Item;
use App\Enum\Dictionary\DictionaryNameEnum;
use App\Form\Application\AdminType\Attachment\AdminApplicationFileType;
use App\Form\Application\Type\Attachment\ApplicationFileType;
use App\Form\Sanitazer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApplicationAdaptationCardForm extends AbstractType
{

    public function __construct(
        private(set) readonly EntityManagerInterface $entityManager, private readonly Sanitazer $sanitazer,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('files', CollectionType::class, [
                'label' => false,
                'entry_type' => AdminApplicationFileType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => true,
                'prototype' => true,
                'constraints' => [
                    new NotBlank(['message' => $this->translator->trans('Wgraj wymagane pliki')]),
                ],
            ])
            ->add('adaptationCard', EntityType::class, [
                'label' => $this->translator->trans('Okres trwania adaptacji') . '*',
                'required' => false,
                'placeholder' => $this->translator->trans('Wybierz okres trwania'),
                'class' => Item::class,
                'query_builder' => fn(DictionaryItemRepository $repository) => $repository->findAllByDictionaryNameQueryBuilder(DictionaryNameEnum::KARTA_ADAPTACJI),
                'choice_label' => 'value',
                'constraints' => [
                    new NotBlank(['message' => $this->translator->trans('Wybierz okres trwania adaptacji')]),
                ],
            ])
            ->add('adaptationCardIssueDate', DateType::class, [
                'label' => $this->translator->trans('Data wydania karty adaptacji') . '*',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'min' => '1900-01-01',
                    'max' => '2099-12-31',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Wybierz datę wydania adaptacji'),
                    ]),
                ],
            ]);

        $category = $options['category'] ?? null;

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($category) {
            $form = $event->getForm();

            $filesForm = $form->get('files');

            foreach ($filesForm as $childName => $fileForm) {
                $file = $fileForm->getData();

                $fileCategory = $file->category;

                if ($fileCategory === $category) {
                    $filesForm->remove($childName);
                }
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
            'data_class' => Application::class,
            'category' => null,
            'empty_data' => function (FormInterface $form, $data) {
                return new class() extends Application {};
            },
        ]);
    }

}
