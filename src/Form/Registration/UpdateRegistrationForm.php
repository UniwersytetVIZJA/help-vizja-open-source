<?php

namespace App\Form\Registration;

use App\Core\DictionaryItem\DictionaryItemRepository;
use App\Database\Entity\Dictionary\Item;
use App\Database\Entity\Registration;
use App\Database\Entity\User;
use App\Database\Repository\UserRepository;
use App\Enum\Dictionary\DictionaryNameEnum;
use App\Form\Sanitazer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;
use function is_array;
use function sprintf;

class UpdateRegistrationForm extends AbstractType
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
            ->add('title', EntityType::class, [
                'label' => $this->translator->trans('Rodzaj konsultacji'),
                'required' => true,
                'query_builder' => fn(DictionaryItemRepository $repository) => $repository->findAllByDictionaryNameQueryBuilder(DictionaryNameEnum::RODZAJ_KONSULTACJI),
                'class' => Item::class,
                'choice_label' => 'value',
                'constraints' => [
                    new NotBlank(message: 'Wybierz rodzaj konsultacji'),
                ],
            ])
            ->add('language', EntityType::class, [
                'label' => $this->translator->trans('Język konsultacji'),
                'required' => true,
                'query_builder' => fn(DictionaryItemRepository $repository) => $repository->findAllByDictionaryNameQueryBuilder(DictionaryNameEnum::JEZYKI),
                'class' => Item::class,
                'choice_label' => 'value',
                'constraints' => [
                    new NotBlank(message: 'Wybierz język konsultacji'),
                ],
            ])
            ->add('startsAt', DateTimeType::class, [
                'label' => $this->translator->trans('Data i godzina rozpoczęcia'),
                'input' => 'datetime_immutable',
            ])
            ->add('endsAt', DateTimeType::class, [
                'label' => $this->translator->trans('Data i godzina zakończenia'),
                'input' => 'datetime_immutable',
            ])
            ->add('capacity', IntegerType::class, [
                'label' => $this->translator->trans('Maksymalna liczba zapisów'),
                'required' => true,
            ])
            ->add('teamsMeetingUrl', UrlType::class, [
                'label' => $this->translator->trans('Link do spotkania na MS Teams'),
                'required' => false,
                'mapped' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => $this->translator->trans('Opis'),
                'required' => true,
            ])
            ->add('specialist', EntityType::class, [
                'label' => $this->translator->trans('Wybierz specialistę'),
                'choice_label' => static function (User $u) {
                    return sprintf('%s %s', $u->firstName, $u->lastName);
                },
                'choice_value' => 'email',
                'class' => User::class,
                'query_builder' => fn(UserRepository $repository) => $repository->findByRole('ROLE_SPECIALIST'),
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
            'data_class' => Registration::class,
            'translation_domain' => 'messages',
        ]);
    }
}
