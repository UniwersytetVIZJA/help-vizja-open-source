<?php

namespace App\Form\Questionnaire;

use App\Database\Entity\Questionnaire;
use App\Form\Sanitazer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;
use function is_array;

class QuestionnaireForm extends AbstractType
{
    /**
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
            ->add('q1', ChoiceType::class, [
                'label' => $this->translator->trans('Jak oceniasz swoje ogólne doświadczenie korzystania z aplikacji?') . '*',
                'required' => true,
                'expanded' => true,
                'choices' => [
                    $this->translator->trans('Bardzo źle') => 1,
                    $this->translator->trans('Źle') => 2,
                    $this->translator->trans('Przeciętnie') => 3,
                    $this->translator->trans('Dobrze') => 4,
                    $this->translator->trans('Bardzo dobrze') => 5,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Musisz zaznaczyć jedną z opcji'),
                    ])
                ]
            ])
            ->add('q2', ChoiceType::class, [
                'label' => $this->translator->trans('Na ile aplikacja spełnia Twoje oczekiwania?') . '*',
                'required' => true,
                'expanded' => true,
                'choices' => [
                    $this->translator->trans('1') => 1,
                    $this->translator->trans('2') => 2,
                    $this->translator->trans('3') => 3,
                    $this->translator->trans('4') => 4,
                    $this->translator->trans('5') => 5,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Musisz zaznaczyć jedną z opcji'),
                    ])
                ]
            ])
            ->add('q3', ChoiceType::class, [
                'label' => $this->translator->trans('Jak oceniasz łatwość znalezienia informacji lub funkcji, które szukałeś/-aś?') . '*',
                'required' => true,
                'expanded' => true,
                'choices' => [
                    $this->translator->trans('Bardzo źle') => 1,
                    $this->translator->trans('Źle') => 2,
                    $this->translator->trans('Przeciętnie') => 3,
                    $this->translator->trans('Dobrze') => 4,
                    $this->translator->trans('Bardzo dobrze') => 5,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Musisz zaznaczyć jedną z opcji'),
                    ])
                ]
            ])
            ->add('q4', ChoiceType::class, [
                'label' => $this->translator->trans('Jak oceniasz intuicyjność układu elementów na stronie?') . '*',
                'required' => true,
                'expanded' => true,
                'choices' => [
                    $this->translator->trans('Bardzo źle') => 1,
                    $this->translator->trans('Źle') => 2,
                    $this->translator->trans('Przeciętnie') => 3,
                    $this->translator->trans('Dobrze') => 4,
                    $this->translator->trans('Bardzo dobrze') => 5,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Musisz zaznaczyć jedną z opcji'),
                    ])
                ]
            ])
            ->add('q5', ChoiceType::class, options: [
                'label' => $this->translator->trans('Jak oceniasz działanie aplikacji według Twoich oczekiwań?') . '*',
                'required' => true,
                'expanded' => true,
                'choices' => [
                    $this->translator->trans('Bardzo źle') => 1,
                    $this->translator->trans('Źle') => 2,
                    $this->translator->trans('Przeciętnie') => 3,
                    $this->translator->trans('Dobrze') => 4,
                    $this->translator->trans('Bardzo dobrze') => 5,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Musisz zaznaczyć jedną z opcji'),
                    ])
                ]
            ])
            ->add('q6', TextareaType::class, [
                'label' => $this->translator->trans('Czy są funkcje, których Ci brakuje? Jeśli tak, jakie?'),
                'required' => false,
            ])
            ->add('q7', ChoiceType::class, [
                'label' => $this->translator->trans('Jak oceniasz szybkość działania poszczególnych funkcji?') . '*',
                'required' => true,
                'expanded' => true,
                'choices' => [
                    $this->translator->trans('Bardzo źle') => 1,
                    $this->translator->trans('Źle') => 2,
                    $this->translator->trans('Przeciętnie') => 3,
                    $this->translator->trans('Dobrze') => 4,
                    $this->translator->trans('Bardzo dobrze') => 5,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Musisz zaznaczyć jedną z opcji'),
                    ])
                ]
            ])
            ->add('q8', TextareaType::class, [
                'label' => $this->translator->trans('Czy wystąpiły jakieś problemy techniczne? Jeśli tak — jakie?'),
                'required' => false,
            ])
            ->add('q9', ChoiceType::class, [
                'label' => $this->translator->trans('Jak oceniasz czas ładowania stron?') . '*',
                'required' => true,
                'expanded' => true,
                'choices' => [
                    $this->translator->trans('Bardzo źle') => 1,
                    $this->translator->trans('Źle') => 2,
                    $this->translator->trans('Przeciętnie') => 3,
                    $this->translator->trans('Dobrze') => 4,
                    $this->translator->trans('Bardzo dobrze') => 5,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Musisz zaznaczyć jedną z opcji'),
                    ])
                ]
            ])
            ->add('q10', ChoiceType::class, [
                'label' => $this->translator->trans('Jak oceniasz wygląd i estetykę aplikacji?') . '*',
                'required' => true,
                'expanded' => true,
                'choices' => [
                    $this->translator->trans('Bardzo źle') => 1,
                    $this->translator->trans('Źle') => 2,
                    $this->translator->trans('Przeciętnie') => 3,
                    $this->translator->trans('Dobrze') => 4,
                    $this->translator->trans('Bardzo dobrze') => 5,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Musisz zaznaczyć jedną z opcji'),
                    ])
                ]
            ])
            ->add('q11', ChoiceType::class, [
                'label' => $this->translator->trans('Jak oceniasz czytelność tekstów i elementów graficznych?') . '*',
                'required' => true,
                'expanded' => true,
                'choices' => [
                    $this->translator->trans('Bardzo źle') => 1,
                    $this->translator->trans('Źle') => 2,
                    $this->translator->trans('Przeciętnie') => 3,
                    $this->translator->trans('Dobrze') => 4,
                    $this->translator->trans('Bardzo dobrze') => 5,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Musisz zaznaczyć jedną z opcji'),
                    ])
                ]
            ])
            ->add('q12', TextareaType::class, [
                'label' => $this->translator->trans('Co najbardziej podoba Ci się w aplikacji?'),
                'required' => false,
            ])
            ->add('q13', TextareaType::class, [
                'label' => $this->translator->trans('Co chciał(a)byś poprawić w aplikacji?'),
                'required' => false,
            ])
            ->add('q14', TextareaType::class, [
                'label' => $this->translator->trans('Czy masz sugestie dotyczące nowych funkcjonalności lub usprawnień?'),
                'required' => false,
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $data = $this->sanitazer->sanitaze($data, [
                'q6' => ['strip_tags' => true],
                'q8' => ['strip_tags' => true],
                'q12' => ['strip_tags' => true],
                'q13' => ['strip_tags' => true],
                'q14' => ['strip_tags' => true],
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
            'data_class' => Questionnaire::class,
            'translation_domain' => 'messages',
        ]);
    }
}
