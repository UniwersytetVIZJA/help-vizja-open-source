<?php

namespace App\Database\Repository;

use App\Database\Entity\Questionnaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QuestionnaireRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Questionnaire::class);
    }

    /**
     * @param string $question
     * @return float
     */
    public function getAverage(string $question): float
    {
        $avg = $this->createQueryBuilder('q')
            ->select(sprintf('AVG(q.%s) AS avg', $question))
            ->getQuery()
            ->getSingleScalarResult();

        return (float)$avg;
    }
}
