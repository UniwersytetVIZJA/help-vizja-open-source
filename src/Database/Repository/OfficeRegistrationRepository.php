<?php

namespace App\Database\Repository;

use App\Database\Entity\OfficeRegistration;
use App\Database\Entity\Student;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OfficeRegistrationRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OfficeRegistration::class);
    }

    public function findByStudent(Student $student): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.registeredStudents', 'rs')
            ->andWhere('rs.student = :student')
            ->setParameter('student', $student)
            ->orderBy('r.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActive(Student $student): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('a')
            ->innerJoin('a.registeredStudents', 'rs')
            ->andWhere('rs.student = :student')
            ->andWhere('a.startAt >= :now')
            ->setParameter('student', $student)
            ->setParameter('now', $now)
            ->orderBy('a.startAt', 'ASC')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \DateTimeImmutable|null $now
     * @return array
     */
    public function findArchive(Student $student): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('a')
            ->innerJoin('a.registeredStudents', 'rs')
            ->andWhere('rs.student = :student')
            ->andWhere('rs.meetingMode IS NOT NULL')
            ->andWhere('a.startAt < :now')
            ->setParameter('student', $student)
            ->setParameter('now', $now)
            ->orderBy('a.startAt', 'DESC')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    public function findActiveRegistrationAdmin(): array
    {
        $now ??= new \DateTimeImmutable();

        return $this->createQueryBuilder('r')
            ->andWhere('r.startAt >= :now')
            ->setParameter('now', $now)
            ->andWhere('r.student IS NOT NULL')
            ->orderBy('r.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findArchiveRegistrationAdmin(): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('r')
            ->innerJoin('r.registeredStudents', 'rs')
            ->andWhere('r.startAt < :now')
            ->setParameter('now', $now)
            ->orderBy('r.startAt', 'ASC')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    public function findForCalendar(\DateTimeInterface $start, \DateTimeInterface $end, Student $student): array
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.registeredStudents', 'rs')
            ->andWhere('rs.student = :student')
            ->andWhere('e.startAt >= :start')
            ->andWhere('e.endAt < :end')
            ->setParameter('student', $student)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('e.startAt', 'ASC')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    public function findRegistrationsforCalendar(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.registeredStudents', 'rs')
            ->andWhere('e.startAt < :end')
            ->andWhere('e.endAt > :start')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('e.startAt', 'ASC')
            ->distinct()
            ->getQuery()
            ->getResult();
    }


}
