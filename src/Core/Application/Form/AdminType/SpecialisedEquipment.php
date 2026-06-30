<?php

declare(strict_types=1);

namespace App\Core\Application\Form\AdminType;

use App\Core\Application\Form\ApplicationFormInterface;
use App\Database\Entity\Application;
use App\Enum\Application\ApplicationTypeEnum;
use App\Form\Application\Type\SpecialisedEquipmentForm;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Class EducationalProcess
 * @package App\Core\Application\Form\Type
 */
#[AutoconfigureTag('application_form')]
final readonly class SpecialisedEquipment implements ApplicationFormInterface
{
    /**
     * EducationalProcess constructor
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(
        private FormFactoryInterface $formFactory,
    ) {}

    /**
     * @param Application $application
     * @return FormInterface
     */
    public function create(Application $application, array $options = []): FormInterface
    {
        return $this->formFactory->create(\App\Form\Application\AdminType\SpecialisedEquipmentForm::class, $application, $options);
    }

    /**
     * @param string $type
     * @return bool
     */
    public function supports(string $type): bool
    {
        if ($type === ApplicationTypeEnum::SPECIALISED_EQUIPMENT->value) {
            return true;
        }

        return false;
    }
}
