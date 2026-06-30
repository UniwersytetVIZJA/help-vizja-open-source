<?php

namespace App\Form\OfficeRegistration;

use App\Database\Entity\OfficeRegistrationRegisteredStudent;
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

class SignUpOfficeRegistrationForm extends AbstractType
{
    public function __construct(private TranslatorInterface $translator, private readonly Sanitazer $sanitazer)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('meetingMode', ChoiceType::class, [
                'label' => $this->translator->trans('Wybierz tryb spotkania') . '*',
                'choices' => [
                    $this->translator->trans('Spotkanie w siedzibie biura (ul. Okopowa 59, Warszawa)') => 'Spotkanie stacjonarne',
                    $this->translator->trans('Spotkanie online (MS Teams)') => 'Spotkanie online',
                ],
                'expanded' => false,
                'required' => true,
                'multiple' => false,
                'mapped' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Wybierz typ spotkania'),
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => $this->translator->trans('Opisz sprawę, z którą zgłaszasz się do BON') . '*',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => $this->translator->trans('Musisz opisać sprawę, z którą chcesz się zgłosić'),
                    ]),
                ],
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $data = $this->sanitazer->sanitaze($data, [
                'description' => ['strip_tags' => true],
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
            'data_class' => OfficeRegistrationRegisteredStudent::class,
        ]);
    }
}
