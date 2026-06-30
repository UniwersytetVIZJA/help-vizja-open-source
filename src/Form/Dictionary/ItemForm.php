<?php

namespace App\Form\Dictionary;

use App\Database\Entity\Dictionary\Item;
use App\Form\Sanitazer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;
use function is_array;

/**
 * Class ItemForm
 * @package App\Form\Admin\Dictionary
 */
class ItemForm extends AbstractType
{
    /**
     * ItemForm constructor
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
        $builder
            ->add('value', TextType::class, [
                'label' => $this->translator->trans('Wartość PL') . '*',
                'required' => true,
            ])
            ->add('valueEnglish', TextType::class, [
                'label' => $this->translator->trans('Wartość ENG'),
                'required' => false,
            ])
            ->add('isActive', ChoiceType::class, [
                'label' => 'Czy aktywny?',
                'choices' => [
                    $this->translator->trans('Aktywny') => 1,
                    $this->translator->trans('Nieaktywny') => 0,

                ],
                'expanded' => false,
                'mapped' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Musisz wybrać typ')
                    ])
                ],
            ])
            ->add('valueKey', TextType::class, [
                'label' => $this->translator->trans('Wartość dodatkowa'),
                'required' => true,
            ])
            ->add('valueKeyEng', TextType::class, [
                'label' => $this->translator->trans('Wartość dodatkowa - Angielska'),
                'required' => false,
            ])
            ->add('valueKey2', TextType::class, [
                'label' => $this->translator->trans('Wartość dodatkowa 2'),
                'required' => true,
            ])
            ->add('valueKey2Eng', TextType::class, [
                'label' => $this->translator->trans('Wartość dodatkowa 2 - Angielska'),
                'required' => false,
            ])
            ->add('displayOrder', IntegerType::class, [
                'label' => $this->translator->trans('Kolejność'),
                'required' => false,
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $data = $this->sanitazer->sanitaze($data, [
                'value' => ['strip_tags' => true],
                'valueEnglish' => ['strip_tags' => true],
                'valueKey' => ['strip_tags' => true],
                'valueKeyEng' => ['strip_tags' => true],
                'valueKey2' => ['strip_tags' => true],
                'valueKey2Eng' => ['strip_tags' => true],
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
            'data_class' => Item::class,
        ]);
    }
}

