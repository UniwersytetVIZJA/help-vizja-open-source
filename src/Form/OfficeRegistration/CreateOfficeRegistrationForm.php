<?php

namespace App\Form\OfficeRegistration;

use App\Database\Entity\OfficeRegistration;
use App\Form\Sanitazer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreateOfficeRegistrationForm extends AbstractType
{
    public function __construct(private TranslatorInterface $translator, private readonly Sanitazer $sanitazer) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('from', DateType::class, [
                'label' => $this->translator->trans('Początek cyklu wizyt od dnia'),
                'widget' => 'single_text',
                'mapped' => false,
                'constraints' => [new NotBlank()],
            ])
            ->add('to', DateType::class, [
                'label' => $this->translator->trans('Zakończenie cyklu wizyt w dniu'),
                'widget' => 'single_text',
                'mapped' => false,
                'constraints' => [new NotBlank()],
            ])
            ->add('timeFrom', TimeType::class, [
                'label' => $this->translator->trans('Godzina rozpoczęcia'),
                'widget' => 'single_text',
                'mapped' => false,
                'with_seconds' => false,
                'constraints' => [new NotBlank()],
            ])
            ->add('timeTo', TimeType::class, [
                'label' => $this->translator->trans('Godzina zakończenia'),
                'widget' => 'single_text',
                'mapped' => false,
                'with_seconds' => false,
                'constraints' => [new NotBlank()],
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OfficeRegistration::class,
        ]);
    }
}
