<?php

declare(strict_types=1);

namespace App\Core\Dictionary;

use App\Database\Entity\Dictionary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class DictionaryRepository
 * @package App\Core\Dictionary
 */
class DictionaryRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    /**
     * DictionaryRepository constructor
     * @param ManagerRegistry $managerRegistry
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ManagerRegistry $managerRegistry, EntityManagerInterface $entityManager)
    {
        parent::__construct($managerRegistry, Dictionary::class);
        $this->entityManager = $entityManager;
    }

    /**
     * @return array
     */
    public function findAll(): array
    {
        return $this->findBy([], [
            'name' => 'ASC',
        ]);
    }

    /**
     * @param string $dictionaryId
     * @return Dictionary|null
     */
    public function findOneById(string $dictionaryId): ?Dictionary
    {
        return $this->find($dictionaryId);
    }

    public function findFilter(?string $dictionary): array
    {
        $qb = $this->entityManager
            ->getRepository(Dictionary::class)
            ->createQueryBuilder('u')
            ->orderBy('u.name', 'ASC');

        if ($dictionary !== null && $dictionary !== '') {
            $qb
                ->andWhere('u.name LIKE :dictionary')
                ->setParameter('dictionary', '%' . $dictionary . '%');
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

}
