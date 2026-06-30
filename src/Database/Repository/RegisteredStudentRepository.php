<?php

namespace App\Database\Repository;

use App\Database\Entity\RegisteredStudent;
use App\Database\Entity\Student;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class RegisteredStudentRepository extends ServiceEntityRepository
{

    /**
     * @param ManagerRegistry $managerRegistry
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ManagerRegistry $managerRegistry, EntityManagerInterface $entityManager)
    {
        parent::__construct($managerRegistry, RegisteredStudent::class);
    }

    /**
     * @param string $student
     * @param \DateTimeImmutable|null $now
     * @return RegisteredStudent|null
     */
    public function findNextForStudent(string $student, ?\DateTimeImmutable $now = null): ?RegisteredStudent
    {
        $now ??= new \DateTimeImmutable();

        return $this->createQueryBuilder('rs')
            ->innerJoin('rs.student', 's')
            ->andWhere('s.id = :student')
            ->setParameter('student', $student)
            ->innerJoin('rs.registration', 'r')
            ->andWhere('r.endsAt IS NULL OR r.endsAt > :now')
            ->setParameter('now', $now)
            ->orderBy('r.startsAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $studentId
     * @return int
     */
    public function countByStudent(string $studentId): int
    {
        return (int)$this->createQueryBuilder('rs')
            ->select('COUNT(rs.id)')
            ->innerJoin('rs.student', 's')
            ->andWhere('s.id = :studentId')
            ->setParameter('studentId', $studentId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findUpcoming(Student $student): array
    {
        $today = new \DateTimeImmutable();

        return $this->createQueryBuilder('rs')
            ->innerJoin('rs.registration', 'r')
            ->andWhere('rs.student = :student')
            ->andWhere('r.startsAt >= :today')
            ->setParameter('student', $student)
            ->setParameter('today', $today)
            ->orderBy('r.startsAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findArchive(Student $student): array
    {
        $today = new \DateTimeImmutable();

        return $this->createQueryBuilder('rs')
            ->innerJoin('rs.registration', 'r')
            ->andWhere('rs.student = :student')
            ->andWhere('r.startsAt < :today')
            ->setParameter('student', $student)
            ->setParameter('today', $today)
            ->orderBy('r.startsAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findForReminder(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('rs')
            ->join('rs.registration', 'r')
            ->join('rs.student', 's')
            ->andWhere('rs.meetingMode IS NOT NULL')
            ->andWhere('rs.reminderSentAt IS NULL')
            ->andWhere('r.startAt >= :from')
            ->andWhere('r.startAt < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('r.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

}
