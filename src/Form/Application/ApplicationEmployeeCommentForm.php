<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Database\Entity\Application;
use App\Form\Sanitazer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use function is_array;

/**
 * Class ApplicationTypeForm
 * @package App\Form\Application
 */
class ApplicationEmployeeCommentForm extends AbstractType
{
    /**
     * ApplicationTypeForm constructor
     * @param EntityManagerInterface $entityManager
     * @param Sanitazer $sanitazer
     */
    public function __construct(
        private(set) readonly EntityManagerInterface $entityManager, private readonly Sanitazer $sanitazer
    ) {}

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('employeeComment', TextareaType::class, [
                'required' => false,
                'attr' => ['rows' => 5],
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $data = $this->sanitazer->sanitaze($data, [
                'employeeComment' => ['strip_tags' => true],
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
            'csrf_protection' => false,
            'data_class' => Application::class,
            'empty_data' => function (FormInterface $form, $data) {
                return new class() extends Application {};
            },
        ]);
    }
}
