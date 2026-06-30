<?php

namespace App\Database\Repository;

use App\Database\Entity\Dictionary\Item;
use App\Database\Entity\InventoryType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class InventoryTypeRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $managerRegistry
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ManagerRegistry $managerRegistry, EntityManagerInterface $entityManager)
    {
        parent::__construct($managerRegistry, InventoryType::class);
    }

    /**
     * @param Item $item
     * @return array
     */
    public function findByDictionaryItemType(Item $item, ?string $search = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.inv', 'eq')
            ->addSelect('eq')
            ->andWhere('i.inv = :item')
            ->setParameter('item', $item)
            ->orderBy('i.id', 'DESC');

        if ($search) {
            $qb
                ->andWhere('LOWER(i.type) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        return $qb;
    }

    /**
     * @return array
     */
    public function countByType(): array
    {
        return $this->createQueryBuilder('i')
            ->select('t.id AS typeId, COUNT(i.id) AS cnt')
            ->join('i.inv', 't')
            ->groupBy('t.id')
            ->getQuery()->getArrayResult();
    }

    /**
     * @param string $inventoryTypeId
     * @return InventoryType|null
     */
    public function findOneById(string $inventoryTypeId): ?InventoryType
    {
        return $this->find($inventoryTypeId);
    }

}
