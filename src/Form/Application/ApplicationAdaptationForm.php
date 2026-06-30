<?php

namespace App\Form\Application;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApplicationAdaptationForm extends AbstractType
{
    /**
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface $translator
     */
    public function __construct(
        private(set) readonly EntityManagerInterface $entityManager,
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
            ->add('adaptation', ChoiceType::class, [
                'label' => false,
                'choices' => [
                    $this->translator->trans('Nie mam karty adaptacji') => 1,
                    $this->translator->trans('Mam kartę adaptacji') => 2,
                ],
                'choice_attr' => function ($choice, string $label, mixed $value): array {
                    return [
                        'data-description' => match ((string)$value) {
                            '1' => $this->translator->trans('Aby skorzystać z tej usługi musisz posiadać przyznaną kartę adaptacji. Poprosimy Cię o złożenie wniosku o jej przyznanie.'),
                            '2' => $this->translator->trans('Karta adaptacji jest niezbędna do przyznania tłumacza języka migowego lub wsparcia asystenta dydaktycznego.'),
                            default => '',
                        },
                    ];
                },
                'expanded' => true,
                'multiple' => false,
                'mapped' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Musisz wybrać typ'),
                    ]),
                ],
                'attr' => [
                    'class' => 'hidden peer',
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
        ]);
    }
}
