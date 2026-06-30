<?php

declare(strict_types=1);

namespace App\Core;

use App\Database\Entity\BaseEntity;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class BasePersister
 * @package App\Core
 */
class BasePersister
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * BasePersister constructor
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * @param BaseEntity $baseEntity
     * @param bool|null $flush
     * @return BaseEntity
     */
    public function create(BaseEntity $baseEntity, ?bool $flush = false): BaseEntity
    {
        $this->entityManager->persist($baseEntity);

        if (true === $flush) {
            $this->entityManager->flush();
        }

        return $baseEntity;
    }

    /**
     * @param BaseEntity $baseEntity
     * @param bool|null $flush
     * @return void
     */
    public function delete(BaseEntity $baseEntity, ?bool $flush = false): void
    {
        $this->entityManager->remove($baseEntity);

        if (true === $flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * @param BaseEntity $baseEntity
     * @param bool|null $flush
     * @return BaseEntity
     */
    public function update(BaseEntity $baseEntity, ?bool $flush = false): BaseEntity
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();
        $unitOfWork->computeChangeSets();
        $changes = $unitOfWork->getEntityChangeSet($baseEntity);

        if (true === $flush) {
            $this->entityManager->flush();
        }

        return $baseEntity;
    }
}
