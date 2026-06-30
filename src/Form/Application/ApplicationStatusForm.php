<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Database\Entity\Application;
use App\Enum\Dictionary\DictionaryNameEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ApplicationStatusForm
 * @package App\Form\Application
 */
class ApplicationStatusForm extends AbstractType
{
    /**
     * ApplicationTypeForm constructor
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private(set) readonly EntityManagerInterface $entityManager
    ) {}

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('test', HiddenType::class, [
                'mapped' => false,
            ]);
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
            'dictionary_name' => DictionaryNameEnum::TYPY_WNIOSKOW,
        ]);
    }
}
