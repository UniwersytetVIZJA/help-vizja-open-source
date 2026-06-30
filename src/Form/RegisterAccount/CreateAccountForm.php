<?php

namespace App\Form\RegisterAccount;

use App\Database\Entity\Student;
use App\Enum\User\NotificationLanguageEnum;
use App\Form\Sanitazer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;
use function is_array;

class CreateAccountForm extends AbstractType
{
    public function __construct(private TranslatorInterface $translator, private readonly Sanitazer $sanitazer) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => $this->translator->trans('Imię') . '*',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => $this->translator->trans('Podaj imię'),
                    ]),
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => $this->translator->trans('Nazwisko') . '*',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => $this->translator->trans('Podaj nazwisko'),
                    ]),
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => $this->translator->trans('Adres e-mail') . '*',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => $this->translator->trans('Podaj adres e-mail'),
                    ]),
                    new Assert\Email([
                        'message' => $this->translator->trans('Podaj poprawny adres e-mail'),
                    ]),
                    new Assert\Regex([
                        'pattern' => '/@(vizja\.pl|students\.vizja\.pl)$/i',
                        'match' => false,
                        'message' => $this->translator->trans('Nie możesz użyć adresu w domenie vizja.pl lub students.vizja.pl'),
                    ]),
                ]
            ])
            ->add('notificationLanguage', ChoiceType::class, [
                'label' => $this->translator->trans('Język powiadomień'),
                'choices' => [
                    $this->translator->trans('Polski') => NotificationLanguageEnum::Polski->value,
                    $this->translator->trans('Angielski') => NotificationLanguageEnum::Angielski->value,
                ],
                'data' => NotificationLanguageEnum::Polski->value,
                'placeholder' => false,
                'required' => true,
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'first_options' => [
                    'label' => $this->translator->trans('Hasło') . '*',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => $this->translator->trans('Powtórz hasło') . '*',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => $this->translator->trans('Pola Hasło i pole Powtórz hasło muszą być identyczne'),
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => $this->translator->trans('Wpisz hasło'),
                    ]),
                    new Assert\Length([
                        'min' => 12,
                        'minMessage' => $this->translator->trans('Hasło musi mieć co najmniej {{ limit }} znaków'),
                        'max' => 4096,
                    ]),
                    new Assert\NotCompromisedPassword([
                        'message' => $this->translator->trans('To hasło było ujawnione w wyciekach – wybierz inne'),
                    ]),
                    new Assert\Regex([
                        'pattern' => '/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s])/',
                        'message' => $this->translator->trans('Hasło musi zawierać małą i wielką literę, cyfrę i znak specjalny'),
                    ]),
                ],
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $data = $this->sanitazer->sanitaze($data, [
                'firstName' => ['strip_tags' => true],
                'lastName' => ['strip_tags' => true],
                'email' => ['strip_tags' => true],
                'password' => ['strip_tags' => true],
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
            'attr' => [
                'novalidate' => 'novalidate',
            ],
            'data_class' => Student::class,
            'translation_domain' => 'messages',
        ]);
    }
}
