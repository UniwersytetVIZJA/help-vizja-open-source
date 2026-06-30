<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Database\Entity\Application;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ApplicationTypeForm
 * @package App\Form\Application
 */
class ApplicationStatusEditForm extends AbstractType
{
    /**
     * ApplicationTypeForm constructor
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
            ->add('status', ChoiceType::class, [
                'label' => false,
                'required' => false,
                'placeholder' => false,
                'choices' => [
                    $this->translator->trans('Nowy') => 'Nowy',
                    $this->translator->trans('W trakcie') => 'W trakcie',
                    $this->translator->trans('Zaakceptowany') => 'Zaakceptowany',
                    $this->translator->trans('Odrzucony') => 'Odrzucony',
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
            'empty_data' => function (FormInterface $form, $data) {
                return new class() extends Application {};
            },
        ]);
    }
}
