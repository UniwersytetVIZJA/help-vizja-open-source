<?php

declare(strict_types=1);

namespace App\Core\Application\Form;

use App\Database\Entity\Application;
use Symfony\Component\Form\FormInterface;

/**
 * Interface ApplicationFormInterface
 * @package App\Core\Application\Form
 */
interface ApplicationFormInterface
{
    /**
     * @param string $type
     * @return bool
     */
    public function supports(string $type): bool;

    public function create(Application $application, array $options = []): FormInterface;
}
