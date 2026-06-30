<?php

namespace App\Database\Repository\Application;

use App\Database\Entity\Application;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LanguageInterpreterRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Application\LanguageInterpreter::class);
    }

    /**
     * @param string $applicationId
     * @return Application\LanguageInterpreter|null
     */
    public function findOneById(string $applicationId): ?Application\LanguageInterpreter
    {
        return $this->find($applicationId);
    }

    /**
     * @param string|null $student
     * @param string|null $status
     * @param \DateTimeImmutable|null $from
     * @param \DateTimeImmutable|null $to
     * @return array
     */
    public function findByFilter(?string $student, ?string $status, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array
    {
        $qb = $this->createQueryBuilder('a')
            ->addSelect('s')->leftJoin('a.student', 's')
            ->orderBy('a.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('a.status = :status')->setParameter('status', $status);
        }
        if ($student) {
            $qb->andWhere('s.firstName', 's.lastName');
        }
        if ($from) {
            $qb->andWhere('a.createdAt >= :from')->setParameter('from', $from);
        }
        if ($to) {
            $qb->andWhere('a.createdAt <= :to')->setParameter('to', $to);
        }

        return $qb->getQuery()->getResult();
    }

}
