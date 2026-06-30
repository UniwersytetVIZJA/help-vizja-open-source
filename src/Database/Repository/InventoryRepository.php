<?php

namespace App\Database\Repository;

use App\Database\Entity\Dictionary\Item;
use App\Database\Entity\Inventory;
use App\Database\Entity\InventoryType;
use App\Enum\Dictionary\DictionaryNameEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class InventoryRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $managerRegistry
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ManagerRegistry $managerRegistry, EntityManagerInterface $entityManager)
    {
        parent::__construct($managerRegistry, Inventory::class);
    }

    /**
     * @param DictionaryNameEnum $dictionaryName
     * @return QueryBuilder
     */
    public function findAllByDictionaryNameQueryBuilder(DictionaryNameEnum $dictionaryName): QueryBuilder
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $queryBuilder->from(Item::class, 'item')
            ->select('item')
            ->join('item.dictionary', 'dictionary')
            ->andWhere('dictionary.name = :dictionaryName')
            ->andWhere('item.isActive = :active')
            ->addOrderBy('item.value', 'ASC')
            ->setParameter('dictionaryName', $dictionaryName)
            ->setParameter('active', 1)
            ->getQuery()
            ->getResult();

        return $queryBuilder;
    }

    /**
     * @param InventoryType $item
     * @return array
     */
    public function findByDictionaryItem(InventoryType $item): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.equipment', 'eq')->addSelect('eq')
            ->andWhere('i.equipment = :item')
            ->setParameter('item', $item)
            ->orderBy('i.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param InventoryType $type
     * @return Inventory|null
     */
    public function findOneFreeByTypeForUpdate(InventoryType $type): ?Inventory
    {
        $qb = $this->createQueryBuilder('i')
            ->andWhere('i.equipment = :type')
            ->andWhere('i.status = :free')
            ->setParameter('type', $type)
            ->setParameter('free', 'Dostępny')
            ->setMaxResults(1);

        $inv = $qb->getQuery()->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)->getOneOrNullResult();

        return $inv;
    }

    /**
     * @param array $typeIds
     * @return array
     */
    public function countAvailableByTypeIds(array $typeIds): array
    {
        if (!$typeIds) return [];
        $rows = $this->createQueryBuilder('i')
            ->select('IDENTITY(i.equipment) AS t, COUNT(i.id) AS c')
            ->andWhere('i.equipment IN (:ids)')
            ->andWhere('i.status = :free')
            ->setParameter('ids', $typeIds)
            ->setParameter('free', 'Dostępny')
            ->groupBy('t')
            ->getQuery()->getArrayResult();

        $out = [];
        foreach ($rows as $r) $out[$r['t']] = (int)$r['c'];

        return $out;
    }

    /**
     * @param string $itemId
     * @param string $typeId
     * @param \DateTimeImmutable|null $start
     * @param \DateTimeImmutable|null $end
     * @return Inventory|null
     */
    public function findFirstAvailableByItemAndType(string $itemId, string $typeId, ?\DateTimeImmutable $start, ?\DateTimeImmutable $end): ?Inventory
    {
        $qb = $this->createQueryBuilder('i')
            ->andWhere('i.item = :itemId')
            ->andWhere('i.type = :typeId')
            ->andWhere('i.status = :free')
            ->setParameter('itemId', $itemId)
            ->setParameter('typeId', $typeId)
            ->setParameter('free', 'Dostępny')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array $types
     * @return array
     */
    public function countByTypes(array $types): array
    {
        $qb = $this->createQueryBuilder('i')
            ->select('IDENTITY(i.equipment) AS type_id, COUNT(i.id) AS cnt')
            ->where('i.equipment IN (:types)')
            ->setParameter('types', $types)
            ->groupBy('type_id');

        $rows = $qb->getQuery()->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(string)$row['type_id']] = (string)$row['cnt'];
        }

        return $result;
    }

    public function countByTypeIds(array $typeIds): array
    {
        if ($typeIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('i')
            ->select('e.id AS typeId, COUNT(i.id) AS countItems')
            ->join('i.equipment', 'e')
            ->andWhere('e.id IN (:typeIds)')
            ->setParameter('typeIds', $typeIds)
            ->groupBy('e.id')
            ->getQuery()
            ->getArrayResult();

        $result = [];

        foreach ($rows as $row) {
            $result[(string)$row['typeId']] = (int)$row['countItems'];
        }

        return $result;
    }

}
