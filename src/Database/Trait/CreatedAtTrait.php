<?php

declare(strict_types=1);

namespace App\Database\Trait;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;

/**
 * Trait CreatedAtTrait
 * @package App\Database\Trait
 */
trait CreatedAtTrait
{
    /**
     * @var DateTimeImmutable
     */
    #[Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $createdAt;

    /**
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @param DateTimeImmutable $createdAt
     * @return \App\Database\Entity\BaseEntity|CreatedAtTrait
     */
    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
