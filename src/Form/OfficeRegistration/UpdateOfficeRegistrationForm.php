<?php

namespace App\Form\OfficeRegistration;

use App\Database\Entity\OfficeRegistration;
use App\Form\Sanitazer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class UpdateOfficeRegistrationForm extends AbstractType
{
    public function __construct(private TranslatorInterface $translator, private readonly Sanitazer $sanitazer) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startAt', DateTimeType::class, [
                'label' => $this->translator->trans('Data i godzina rozpoczęcia'),
                'input' => 'datetime_immutable',
                'required' => true,
                'widget' => 'single_text',
                'html5' => true,
                'constraints' => [
                    new NotBlank(message: 'Wybierz datę i godzinę wizyty'),
                ],
            ])
            ->add('endAt', DateTimeType::class, [
                'label' => $this->translator->trans('Data i godzina zakończenia'),
                'input' => 'datetime_immutable',
                'required' => true,
                'widget' => 'single_text',
                'html5' => true,
                'constraints' => [
                    new NotBlank(message: 'Wybierz datę i godzinę wizyty'),
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
            'data_class' => OfficeRegistration::class,
            'csrf_protection' => false,
        ]);
    }
}
