<?php

namespace App\Form\Announcement;

use App\Database\Entity\Announcements;
use App\Form\Sanitazer;
use Exercise\HTMLPurifierBundle\HTMLPurifiersRegistryInterface;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use function is_array;

class CreateAnnouncementForm extends AbstractType
{
    /**
     * @param TranslatorInterface $translator
     * @param HTMLPurifiersRegistryInterface $purifier
     * @param Sanitazer $sanitazer
     */
    public function __construct(private TranslatorInterface $translator, private readonly HTMLPurifiersRegistryInterface $purifier, private readonly Sanitazer $sanitazer) {}

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => $this->translator->trans('Tytuł ogłoszenia'),
                'required' => true,
            ])
            ->add('startsAt', DateTimeType::class, [
                'label' => $this->translator->trans('Data i godzina publikacji'),
                'input' => 'datetime_immutable',
                'html5' => true,
                'widget' => 'single_text',
            ])
            ->add('expiresAt', DateTimeType::class, [
                'label' => $this->translator->trans('Data i godzina zakończenia publikacji'),
                'input' => 'datetime_immutable',
                'html5' => true,
                'widget' => 'single_text',
            ])
            ->add('description', CKEditorType::class, [
                'label' => false,
                'config' => [
                    'toolbar' => [
                        ['Bold', 'Italic', 'Underline'],
                        ['NumberedList', 'BulletedList'],
                        ['Link', 'Unlink'],
                        ['Image', 'Table'],
                        ['Undo', 'Redo'],
                        ['Source'],
                    ],
                    'height' => 320,
                    'extraAllowedContent' => true,
                    'removePlugins' => 'elementspath',
                    'resize_enabled' => false,
                    'format_tags' => 'p;h2;h3;h4;pre',
                    'removeButtons' => 'Save,NewPage,Preview,Print,Templates,Cut,Copy,Paste,PasteText,PasteFromWord,Scayt,About,Maximize',
                ],
            ])
            ->add('published', ChoiceType::class, [
                'label' => 'Czy opublikować?',
                'choices' => [
                    'Tak' => true,
                    'Nie' => false,
                ],
                'expanded' => false,
                'multiple' => false,
                'mapped' => true,
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $data = $this->sanitazer->sanitaze($data, [
                'title' => ['strip_tags' => true],
            ]);

            if (isset($data['description'])) {
                $data['description'] = $this->purifier
                    ->get('default')
                    ->purify((string)$data['description']);
            }

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
            'data_class' => Announcements::class,
        ]);
    }

}
