<?php

declare(strict_types=1);

namespace App\Database\Trait;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait IdTrait
 * @package App\Database\Trait
 */
trait IdTrait
{
    /**
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    public string $id {
        get {
            return $this->id;
        }
    }
}
