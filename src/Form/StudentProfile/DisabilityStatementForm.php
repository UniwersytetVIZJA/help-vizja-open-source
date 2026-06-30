<?php

namespace App\Form\StudentProfile;

use App\Core\DictionaryItem\DictionaryItemRepository;
use App\Database\Entity\Dictionary\Item;
use App\Database\Entity\Student;
use App\Enum\Dictionary\DictionaryNameEnum;
use App\Form\Sanitazer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class DisabilityStatementForm extends AbstractType
{
    public function __construct(private TranslatorInterface $translator, private readonly Sanitazer $sanitazer) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('disabilityType', EntityType::class, [
                'label' => $this->translator->trans('Rodzaj niepełnosprawności'),
                'required' => true,
                'placeholder' => $this->translator->trans('Wybierz'),
                'class' => Item::class,
                'query_builder' => fn(DictionaryItemRepository $repository) => $repository->findAllByDictionaryNameQueryBuilder(DictionaryNameEnum::RODZAJ_NIEPELNOSPRAWNOSCI),
                'choice_label' => 'value',
            ])
            ->add('disabilityDegree', EntityType::class, [
                'label' => $this->translator->trans('Stopień niepełnosprawności'),
                'required' => true,
                'placeholder' => $this->translator->trans('Wybierz'),
                'class' => Item::class,
                'query_builder' => fn(DictionaryItemRepository $repository) => $repository->findAllByDictionaryNameQueryBuilder(DictionaryNameEnum::STOPIEN_NIEPELNOSPRAWNOSCI),
                'choice_label' => 'value',
            ])
            ->add('disabilityExpiration', DateType::class, [
                'label' => $this->translator->trans('Data ważności orzeczenia'),
                'input' => 'datetime_immutable',
                'required' => true,
                'widget' => 'single_text',
                'html5' => true,
                'constraints' => [
                    new NotBlank(message: 'Wybierz datę ważności'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Student::class,
            'translation_domain' => 'messages',
        ]);
    }
}
