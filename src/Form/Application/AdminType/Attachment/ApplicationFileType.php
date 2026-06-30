<?php

namespace App\Form\Application\AdminType\Attachment;

use App\Database\Entity\File;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vich\UploaderBundle\Form\Type\VichFileType;

class ApplicationFileType extends AbstractType
{
    /**
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface $translator
     */
    public function __construct(
        private(set) readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('file', VichFileType::class, [
            'label' => false,
            'required' => false,
            'mapped' => true,
            'download_uri' => false,
            'allow_delete' => true,
            'by_reference' => false,
            'constraints' => [
                new FileConstraint([
                    'maxSize' => '50M',
                    'mimeTypes' => ['application/pdf'],
                    'mimeTypesMessage' => $this->translator->trans('Obsługiwany format pliku to PDF'),
                ])
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
            'data_class' => File::class,
        ]);
    }
}
