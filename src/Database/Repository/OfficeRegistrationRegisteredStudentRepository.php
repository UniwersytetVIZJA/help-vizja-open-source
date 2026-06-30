<?php

namespace App\Database\Repository;

use App\Core\BasePersister;
use App\Database\Entity\OfficeRegistration;
use App\Database\Entity\OfficeRegistrationRegisteredStudent;
use App\Database\Entity\Student;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OfficeRegistrationRegisteredStudentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly BasePersister $basePersister)
    {
        parent::__construct($registry, OfficeRegistrationRegisteredStudent::class);
    }

    public function update(OfficeRegistrationRegisteredStudent $registration): void
    {
        $this->basePersister->update($registration, true);
    }

    public function findByStudent(Student $student): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('rs')
            ->innerJoin('rs.registration', 'r')
            ->addSelect('r')
            ->andWhere('rs.student = :student')
            ->andWhere('rs.meetingMode IS NOT NULL')
            ->andWhere('r.startAt >= :now')
            ->setParameter('student', $student)
            ->setParameter('now', $now)
            ->orderBy('r.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveMeetings(string $id): array
    {
        return $this->createQueryBuilder('rs')
            ->distinct()
            ->innerJoin('rs.registration', 'r')
            ->addSelect('rs')
            ->andWhere('r.id = :id')
            ->andWhere('rs.meetingMode IS NOT NULL')
            ->setParameter('id', $id)
            ->addSelect('IF(rs.confirmed IS NULL, 2, rs.confirmed) as HIDDEN sort_confirmed')
            ->orderBy('sort_confirmed', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findInactiveMeetings(): array
    {
        return $this->createQueryBuilder('rs')
            ->distinct()
            ->addSelect('rs')
            ->andWhere('rs.meetingMode IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function findActiveByRegistration(OfficeRegistration $registration): ?OfficeRegistrationRegisteredStudent
    {
        return $this->createQueryBuilder('rs')
            ->andWhere('rs.registration = :registration')
            ->andWhere('rs.confirmed = true OR rs.meetingMode IS NOT NULL')
            ->setParameter('registration', $registration)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPendingForStudent(OfficeRegistration $registration, $student): ?OfficeRegistrationRegisteredStudent
    {
        return $this->createQueryBuilder('rs')
            ->andWhere('rs.registration = :registration')
            ->andWhere('rs.student = :student')
            ->andWhere('rs.confirmed IS NULL')
            ->setParameter('registration', $registration)
            ->setParameter('student', $student)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findForReminder(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('rs')
            ->join('rs.registration', 'r')
            ->join('rs.student', 's')
            ->andWhere('rs.confirmed = true')
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
