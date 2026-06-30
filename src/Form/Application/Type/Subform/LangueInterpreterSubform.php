<?php

declare(strict_types=1);

namespace App\Form\Application\Type\Subform;

use App\Core\DictionaryItem\DictionaryItemRepository;
use App\Database\Entity\Dictionary\Item;
use App\Enum\Dictionary\DictionaryNameEnum;
use App\Form\Sanitazer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use function is_array;

/**
 * Class LanguageInterpreterForm
 * @package App\Form\Application\Type
 */
class LangueInterpreterSubform extends AbstractType
{
    /**
     * LanguageInterpreterForm constructor
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface $translator
     * @param Sanitazer $sanitazer
     */
    public function __construct(
        private(set) readonly EntityManagerInterface $entityManager, private readonly TranslatorInterface $translator, private readonly Sanitazer $sanitazer
    ) {}

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('justification', TextareaType::class, [
                'label' => $this->translator->trans('Uzasadnienie') . '*',
                'required' => true,
            ])
            ->add('adaptation_dictionary', EntityType::class, [
                'label' => $this->translator->trans('Rodzaj adaptacji'),
                'required' => true,
                'expanded' => true,
                'multiple' => true,
                'class' => Item::class,
                'query_builder' => fn(DictionaryItemRepository $repository) => $repository->findAllByDictionaryNameQueryBuilder(DictionaryNameEnum::PRZYZNANIE_KIEDY),
                'choice_label' => 'value',
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $data = $this->sanitazer->sanitaze($data, [
                'justification' => ['strip_tags' => true],
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
            'csrf_protection' => false,
            'data_class' => null,
        ]);
    }
}
