<?php

declare(strict_types=1);

namespace App\Database\Trait;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;

/**
 * Trait UpdatedAtTrait
 * @package App\Database\Trait
 */
trait UpdatedAtTrait
{
    /**
     * @var DateTimeImmutable|null
     */
    #[Column(type: 'datetime_immutable', nullable: true)]
    protected ?DateTimeImmutable $updatedAt;

    /**
     * @return DateTimeImmutable
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTimeImmutable|null $updatedAt
     * @return $this
     */
    public function setUpdatedAt(?DateTimeImmutable $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
