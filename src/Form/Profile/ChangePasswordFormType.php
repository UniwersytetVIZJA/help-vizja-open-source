<?php

namespace App\Form\Profile;

use App\Database\Entity\Student;
use App\Form\Sanitazer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChangePasswordFormType extends AbstractType
{
    public function __construct(private TranslatorInterface $translator, private readonly Sanitazer $sanitazer) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'first_options' => [
                    'label' => 'Hasło' . '*',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Powtórz hasło' . '*',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Hasła muszą być identyczne',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Podaj hasło',
                    ]),
                    new Assert\Length([
                        'min' => 12,
                        'minMessage' => 'Hasło musi mieć co najmniej {{ limit }} znaków',
                        'max' => 4096,
                    ]),
                    new Assert\NotCompromisedPassword([
                        'message' => 'To hasło było ujawnione w wyciekach – wybierz inne',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{12,}/',
                        'message' => 'Hasło musi mieć min. 12 znaków oraz zawierać małą i wielką literę, cyfrę i znak specjalny',
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
            'data_class' => Student::class,
        ]);
    }
}
