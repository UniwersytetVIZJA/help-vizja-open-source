<?php

namespace App\Form\FAQ;

use App\Database\Entity\Faq;
use App\Form\LanguageEnum;
use App\Form\Sanitazer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;
use function is_array;

class FaqForm extends AbstractType
{
    public function __construct(private TranslatorInterface $translator, private readonly Sanitazer $sanitazer) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('question', TextType::class, [
                'label' => $this->translator->trans('Pytanie') . '*',
                'required' => true,
            ])
            ->add('answer', TextareaType::class, [
                'label' => $this->translator->trans('Odpowiedź') . '*',
                'required' => true,
            ])
            ->add('language', EnumType::class, [
                'class' => LanguageEnum::class,
                'label' => $this->translator->trans('Język pytania'),
                'choice_label' => fn(LanguageEnum $case) => $this->translator->trans($case->value),
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Wybierz język pytania'),
                ]
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $data = $this->sanitazer->sanitaze($data, [
                'question' => ['strip_tags' => true],
                'answer' => ['strip_tags' => true],
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
            'data_class' => Faq::class,
        ]);
    }
}
