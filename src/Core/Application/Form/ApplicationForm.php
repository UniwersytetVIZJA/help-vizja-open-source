<?php

declare(strict_types=1);

namespace App\Core\Application\Form;

use App\Database\Entity\Application;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Form\FormInterface;

/**
 * Class ApplicationForm
 * @package App\Core\Application\Form
 */
final readonly class ApplicationForm
{
    /**
     * ApplicationForm constructor
     * @param iterable $types
     */
    public function __construct(
        #[AutowireIterator('application_form')]
        private iterable $types,
    ) {}

    /**
     * @param Application $application
     * @return FormInterface
     */
    public function create(Application $application, array $options = []): FormInterface
    {
        foreach ($this->types as $type) {
            if (true === $type->supports($application->type->value)) {
                return $type->create($application, $options);
            }
        }

        throw new InvalidArgumentException('No form found for given type');
    }
}
