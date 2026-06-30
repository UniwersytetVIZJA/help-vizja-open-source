<?php

namespace App\Form\Inventory;

use App\Database\Entity\Inventory;
use App\Database\Entity\Student;
use App\Form\Sanitazer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use function is_array;
use function sprintf;

class CreateInventoryForm extends AbstractType
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
            ->add('description', TextareaType::class, [
                'label' => 'Opis sprzętu'
            ])
            ->add('serialNumber', TextType::class, [
                'label' => 'Numer seryjny sprzętu'
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status sprzętu',
                'choices' => [
                    'Dostępny' => 'Dostępny',
                    'Niedostępny' => 'Niedostępny',
                    'Zarezerwowany' => 'Zarezerwowany',
                    'Wypożyczony' => 'Wypożyczony',
                    'Uszkodzony' => 'Uszkodzony',
                ],
            ])
            ->add('rentStart', DateType::class, [
                'label' => 'Rozpoczęcie wypożyczenia',
                'input' => 'datetime_immutable',
            ])
            ->add('rentEnd', DateType::class, [
                'label' => 'Zakończenie wypożyczenia',
                'input' => 'datetime_immutable',
            ])
            ->add('student', EntityType::class, [
                'label' => $this->translator->trans('Student'),
                'class' => Student::class,
                'choice_label' => fn (Student $student) => sprintf(
                    '%s %s (%s)',
                    $student->firstName,
                    $student->lastName,
                    $student->email
                ),
                'placeholder' => $this->translator->trans('Wybierz studenta'),
                'required' => true,
                'multiple' => false,
                'expanded' => false,
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $data = $this->sanitazer->sanitaze($data, [
                'description' => ['strip_tags' => true],
                'serialNumber' => ['strip_tags' => true],
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
            'data_class' => Inventory::class,
            'translation_domain' => 'messages',
        ]);
    }
}
