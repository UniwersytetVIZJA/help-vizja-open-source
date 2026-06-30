<?php

declare(strict_types=1);

namespace App\Core\User;

use App\Database\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class UserRepository
 * @package App\Core\User
 */
class UserRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    /**
     * UserRepository constructor
     * @param ManagerRegistry $managerRegistry
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ManagerRegistry $managerRegistry, EntityManagerInterface $entityManager)
    {
        parent::__construct($managerRegistry, User::class);
        $this->entityManager = $entityManager;
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->getAllQueryBuilder()->getQuery()->getResult();
    }

    /**
     * @return QueryBuilder
     */
    public function getAllQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $queryBuilder->from(User::class, 'user')
            ->select('user')
            ->addOrderBy('user.lastName')
            ->addOrderBy('user.firstName');

        return $queryBuilder;
    }

    /**
     * @param string $email
     * @return User|null
     */
    public function getOneByEmail(string $email): ?User
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $queryBuilder->from(User::class, 'user')
            ->select('user')
            ->andWhere('user.email = :email')
            ->setParameter('email', $email);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $userId
     * @return User|null
     */
    public function getOneById(string $userId): ?User
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $queryBuilder->from(User::class, 'user')
            ->select('user')
            ->andWhere('user.id = :userId')
            ->setParameter('userId', $userId);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @return array
     */
    public function findAll(): array
    {
        return $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string|null $user
     * @return array
     */
    public function findFilter(?string $user): array
    {
        $qb = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->orderBy('u.lastName', 'ASC');

        if ($user !== null && $user !== '') {
            $qb
                ->andWhere('u.email LIKE :user OR u.firstName LIKE :user OR u.lastName LIKE :user')
                ->setParameter('user', '%' . $user . '%');
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

}

