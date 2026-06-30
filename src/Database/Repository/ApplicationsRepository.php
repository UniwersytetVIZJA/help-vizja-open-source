<?php

namespace App\Database\Repository;

use App\Application\Enum\ApplicationType;
use App\Database\Entity\Application;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Application|null find($id, $lockMode = null, $lockVersion = null)
 * @method Application|null findOneBy(array $criteria, array $orderBy = null)
 * @method Application[] findAll()
 * @method Application[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApplicationsRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    /**
     * @return mixed
     */
    public function findAllByDate()
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param ApplicationType $applicationType
     * @return mixed
     */
    public function findByType(ApplicationType $applicationType)
    {
        return $this->createQueryBuilder('a')
            ->where('a.type = :type')
            ->orderBy('a.createdAt', 'DESC')
            ->setParameter('type', $applicationType)
            ->getQuery()
            ->getResult();
    }

}
