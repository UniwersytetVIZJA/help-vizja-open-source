<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Core\Application\ApplicationRepository;
use App\Database\Entity\Application;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ApplicationTypeForm
 * @package App\Form\Application
 */
class ApplicationTypeFormHelper extends AbstractType
{
    /**
     * ApplicationTypeForm constructor
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private(set) readonly EntityManagerInterface $entityManager,
        private readonly ApplicationRepository $applicationRepository,
    ) {}

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // brak pól
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Application::class,
        ]);
    }
}
