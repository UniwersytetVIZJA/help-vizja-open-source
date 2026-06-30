<?php

namespace App\Form\Registration;

use App\Database\Entity\OfficeRegistration;
use App\Database\Entity\Registration;
use App\Database\Entity\Student;
use App\Form\Sanitazer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddStudentForm extends AbstractType
{
    public function __construct(private TranslatorInterface $translator) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('registeredStudents', EntityType::class, [
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
                'mapped' => false,
                'multiple' => false,
                'expanded' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Registration::class,
        ]);
    }
}
