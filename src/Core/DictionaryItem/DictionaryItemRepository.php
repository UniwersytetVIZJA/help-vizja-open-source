<?php

declare(strict_types=1);

namespace App\Core\DictionaryItem;

use App\Database\Entity\Dictionary\Item;
use App\Enum\Dictionary\DictionaryNameEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class DictionaryItemRepository
 * @package App\Core\DictionaryItem
 */
class DictionaryItemRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    /**
     * DictionaryItemRepository constructor
     * @param ManagerRegistry $managerRegistry
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ManagerRegistry $managerRegistry, EntityManagerInterface $entityManager)
    {
        parent::__construct($managerRegistry, Item::class);
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $dictionaryId
     * @return array
     */
    public function findAllByDictionaryId(string $dictionaryId): array
    {
        return $this->findBy([
            'dictionary' => $dictionaryId,
        ], [
            'value' => 'ASC',
        ]);
    }

    /**
     * @param string $type
     * @return QueryBuilder
     */
    public function findByType(string $type): QueryBuilder
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.dictionary', 'd')
            ->andWhere('d.name = :dict')
            ->andWhere('i.hiddenValue = :hidden')
            ->setParameter(
                'dict',
                DictionaryNameEnum::TYPY_WNIOSKOW->value ?? DictionaryNameEnum::TYPY_WNIOSKOW
            )
            ->setParameter('hidden', $type)
            ->setMaxResults(1);
    }

    /**
     * @param DictionaryNameEnum $dictionaryName
     * @return array
     */
    public function findAllByDictionaryName(DictionaryNameEnum $dictionaryName): array
    {
        $queryBuilder = $this->findAllByDictionaryNameQueryBuilder($dictionaryName);

        return $queryBuilder->getQuery()->getResult();
    }

    public function findOneByDictionaryNameAndValue(DictionaryNameEnum $dictionaryName, ?string $value): ?Item {
        if (!$value) {
            return null;
        }

        return $this->createQueryBuilder('i')
            ->join('i.dictionary', 'd')
            ->andWhere('d.name = :dictionary')
            ->andWhere('i.value = :value')
            ->setParameter('dictionary', $dictionaryName)
            ->setParameter('value', $value)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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
            ->addOrderBy('item.displayOrder', 'ASC')
            ->addOrderBy('item.value', 'ASC')
            ->setParameter('dictionaryName', $dictionaryName)
            ->setParameter('active', 1);

        return $queryBuilder;
    }

    /**
     * @param string $dictionaryItemId
     * @return Item|null
     */
    public function findOneById(string $dictionaryItemId): ?Item
    {
        return $this->find($dictionaryItemId);
    }

    public function findFilterItem(string $dictionaryId, ?string $item = null): array
    {
        $qb = $this->entityManager
            ->getRepository(Item::class)
            ->createQueryBuilder('u')
            ->andWhere('u.dictionary = :dictId')
            ->setParameter('dictId', $dictionaryId)
            ->orderBy('u.displayOrder', 'ASC');

        if ($item !== null && $item !== '') {
            $qb
                ->andWhere('u.displayOrder LIKE :item')
                ->setParameter('item', '%' . $item . '%');
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

}
