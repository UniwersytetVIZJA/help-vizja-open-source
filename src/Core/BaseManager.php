<?php

declare(strict_types=1);

namespace App\Core;

use Symfony\Contracts\Service\Attribute\Required;

/**
 * Class BaseManager
 * @package App\Core
 */
abstract class BaseManager
{
    /**
     * @var BasePersister
     */
    #[Required]
    public BasePersister $basePersister;

    /**
     * @param BasePersister $basePersister
     * @return void
     */
    public function setBasePersister(BasePersister $basePersister): void
    {
        $this->basePersister = $basePersister;
    }
}
