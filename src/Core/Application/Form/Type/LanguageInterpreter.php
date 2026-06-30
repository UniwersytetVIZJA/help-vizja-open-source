<?php

declare(strict_types=1);

namespace App\Core\Application\Form\Type;

use App\Core\Application\Form\ApplicationFormInterface;
use App\Database\Entity\Application;
use App\Enum\Application\ApplicationTypeEnum;
use App\Form\Application\Type\LanguageInterpreterForm;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Class LanguageInterpreter
 * @package App\Core\Application\Form\Type
 */
#[AutoconfigureTag('application_form')]
final readonly class LanguageInterpreter implements ApplicationFormInterface
{
    /**
     * LanguageInterpreter constructor
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
        return $this->formFactory->create(LanguageInterpreterForm::class, $application, $options);
    }

    /**
     * @param string $type
     * @return bool
     */
    public function supports(string $type): bool
    {
        if ($type === ApplicationTypeEnum::LANGUAGE_INTERPRETER->value) {
            return true;
        }

        return false;
    }
}
