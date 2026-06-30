<?php

declare(strict_types=1);

namespace App\Database\Entity;

use App\Database\Trait\CreatedAtTrait;
use App\Database\Trait\IdTrait;
use App\Database\Trait\UpdatedAtTrait;
use DateTimeImmutable;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Symfony\Component\Uid\Uuid;

/**
 * Class BaseEntity
 * @package App\Database\Entity
 */
#[MappedSuperclass]
#[HasLifecycleCallbacks]
abstract class BaseEntity
{
    use IdTrait;
    use CreatedAtTrait;
    use UpdatedAtTrait;

    /**
     * BaseEntity constructor
     */
    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->id = Uuid::v7()->toString();
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return void
     */
    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new DateTimeImmutable();
        if ($this->createdAt === null) {
            $this->createdAt = $now;
        }
        $this->updatedAt = $now;
    }

    /**
     * @param PreUpdateEventArgs $event
     * @return void
     */
    #[ORM\PreUpdate]
    public function onPreUpdate(PreUpdateEventArgs $event): void
    {
        $this->updatedAt = new DateTimeImmutable();
        $em = $event->getObjectManager();
        $uow = $em->getUnitOfWork();
        $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(static::class), $this
        );
    }
}




