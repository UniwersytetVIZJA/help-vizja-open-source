<?php

namespace App\Database\Repository;

use App\Database\Entity\Faq;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use function Doctrine\ORM\QueryBuilder;

class FaqRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Faq::class);
    }

    public function findFilter(?string $language, ?string $question): array
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');

        if ($language) {
            $qb->andWhere('a.language LIKE :language')
                ->setParameter('language', '%' . $language . '%');
        }

        if ($question) {
            $qb->andWhere('a.question LIKE :question')
                ->setParameter('question', '%' . $question . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
