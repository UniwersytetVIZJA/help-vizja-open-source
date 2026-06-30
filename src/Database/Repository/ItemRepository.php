<?php

namespace App\Database\Repository;

use App\Database\Entity\Dictionary\Item;
use App\Enum\Dictionary\DictionaryNameEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class ItemRepository
 * @package App\Database\Repository
 */
class ItemRepository extends ServiceEntityRepository
{
    /**
     * ItemRepository constructor
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    /**
     * @param DictionaryNameEnum $dictionaryNameEnum
     * @return QueryBuilder
     */
    public function findByDictionaryNameQueryBuilder(DictionaryNameEnum $dictionaryNameEnum): QueryBuilder
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $queryBuilder->from(Item::class, 'item')
            ->select('item')
            ->join('item.dictionary', 'dictionary')
            ->andWhere('dictionary.name = :dictionaryName')
            ->addOrderBy('item.value', Order::Ascending->value)
            ->setParameter('dictionaryName', $dictionaryNameEnum);

        return $queryBuilder;
    }

    /**
     * @param DictionaryNameEnum $dictionary
     * @return array
     */
    public function findByDictionaryName(DictionaryNameEnum $dictionary): array
    {
        return $this->qbByDictionaryName($dictionary)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param DictionaryNameEnum $dictionary
     * @return QueryBuilder
     */
    public function qbByDictionaryName(DictionaryNameEnum $dictionary): QueryBuilder
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.dictionary', 'd')
            ->andWhere('d.name = :name')
            ->setParameter('name', $dictionary->value);
    }
}

