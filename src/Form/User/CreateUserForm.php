<?php

namespace App\Form\User;

use App\Database\Entity\User;
use App\Enum\User\RolesEnum;
use App\Form\Sanitazer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use function is_array;

class CreateUserForm extends AbstractType
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
            ->add('email', TextType::class, [
                'label' => $this->translator->trans('Adres e-mail'),
                'required' => true,
            ])
            ->add('isActive', ChoiceType::class, [
                'label' => $this->translator->trans('Czy aktywny'),
                'choices' => [
                    $this->translator->trans('Tak') => true,
                    $this->translator->trans('Nie') => false,

                ],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => $this->translator->trans('Role'),
                'expanded' => true,
                'multiple' => true,
                'required' => true,
                'choices' => [
                    $this->translator->trans('Administrator') => RolesEnum::ADMIN->value,
                    $this->translator->trans('Pracownik') => RolesEnum::PRACOWNIK->value,
                    $this->translator->trans('Specjalista') => RolesEnum::SPECJALISTA->value,
                ],
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $data = $this->sanitazer->sanitaze($data, [
                'email' => ['strip_tags' => true],
                'firstName' => ['strip_tags' => true],
                'lastName' => ['strip_tags' => true],
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
            'data_class' => User::class,
            'translation_domain' => 'messages',
        ]);
    }
}
