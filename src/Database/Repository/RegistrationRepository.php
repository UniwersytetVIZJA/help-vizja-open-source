<?php

namespace App\Database\Repository;

use App\Database\Entity\Dictionary\Item;
use App\Database\Entity\RegisteredStudent;
use App\Database\Entity\Registration;
use App\Database\Entity\Student;
use App\Database\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ManagerRegistry;

class RegistrationRepository extends ServiceEntityRepository
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ManagerRegistry $managerRegistry, EntityManagerInterface $entityManager)
    {
        parent::__construct($managerRegistry, Registration::class);
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $registrationId
     * @return Registration|null
     */
    public function findOneById(string $registrationId): ?Registration
    {
        return $this->find($registrationId);
    }

    /**
     * @param RegisteredStudent|Student|string $subject
     * @return object|RegisteredStudent|Registration|string|null
     */
    public function findStudentBy(RegisteredStudent|Student|string $subject): ?object
    {
        if ($subject instanceof RegisteredStudent) {
            return $subject;
        }

        if ($subject instanceof Student) {
            return $this->findOneBy(['student' => $subject]);
        }

        if (is_string($subject) && $subject !== '') {
            return $this->find($subject);
        }

        return null;
    }


    /**
     * @param string $registrationId
     * @return int
     */

    /**
     * @param string $registrationId
     * @param string $studentId
     * @return bool
     * @throws ORMException
     */
    public function isRegistered(string $registrationId, string $studentId): bool
    {
        $regRef = $this->entityManager->getReference(Registration::class, $registrationId);
        $stuRef = $this->entityManager->getReference(Student::class, $studentId);

        $count = (int)$this->createQueryBuilder('rs')
            ->select('COUNT(rs.id)')
            ->andWhere('rs.registeredStudents = :registration')
            ->andWhere('rs.registeredStudents = :student')
            ->setParameter('registration', $regRef)
            ->setParameter('student', $stuRef)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @param Student $student
     * @return array
     */
    public function findForCalendar(\DateTimeInterface $start, \DateTimeInterface $end, Student $student): array
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.registeredStudents', 'rs')
            ->andWhere('rs.student = :student')
            ->andWhere('e.startsAt < :end')
            ->andWhere('(e.endsAt IS NULL OR e.endsAt > :start)')
            ->setParameter('start', $start, Types::DATETIME_IMMUTABLE)
            ->setParameter('end', $end, Types::DATETIME_IMMUTABLE)
            ->setParameter('student', $student)
            ->orderBy('e.startsAt', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findSpecialistForCalendar(\DateTimeInterface $start, \DateTimeInterface $end, User $specialist): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.specialist = :specialist')
            ->andWhere('e.startsAt < :end')
            ->andWhere('(e.endsAt IS NULL OR e.endsAt > :start)')
            ->andWhere('e.registered >= 1')
            ->setParameter('start', $start, Types::DATETIME_IMMUTABLE)
            ->setParameter('end', $end, Types::DATETIME_IMMUTABLE)
            ->setParameter('specialist', $specialist)
            ->orderBy('e.startsAt', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \DateTimeImmutable|null $now
     * @return array
     */
    public function findActive(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();

        return $this->createQueryBuilder('a')
            ->andWhere('a.endsAt IS NULL OR a.endsAt > :now')
            ->setParameter('now', $now)
            ->orderBy('a.startsAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \DateTimeImmutable|null $now
     * @return array
     */
    public function findArchive(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();

        return $this->createQueryBuilder('a')
            ->leftJoin('a.registeredStudents', 'rs')
            ->andWhere('a.endsAt IS NULL OR a.endsAt < :now')
            ->andWhere('rs.id IS NOT NULL')
            ->setParameter('now', $now)
            ->orderBy('a.startsAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $specialistId
     * @return array
     */
    public function findBySpecialist(string $specialistId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.specialist = :specialist')
            ->setParameter('specialist', $specialistId)
            ->orderBy('s.startsAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \DateTimeImmutable|null $now
     * @return int
     */
    public function countAllActive(?\DateTimeImmutable $now = null): int
    {
        $now ??= new \DateTimeImmutable();

        return (int)$this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.endsAt IS NULL OR r.endsAt > :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param Student $student
     * @param RegisteredStudent $registration
     * @return void
     */
    public function getInfoFromStudent(Student $student, RegisteredStudent $registration): void
    {
        if ($registration->phone === null && $student->phone !== null) {
            $registration->phone = $student->phone;
        }

        if ($registration->albumNumber === null && $student->albumNumber !== null) {
            $registration->albumNumber = $student->albumNumber;
        }
    }

    public function findFilter(?Item $type, ?Item $language, ?User $specialist, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.specialist', 's')
            ->addSelect('s')
            ->orderBy('a.createdAt', 'DESC');

        if ($specialist) {
            $qb->andWhere('a.specialist = :specialist')
                ->setParameter('specialist', $specialist);
        }

        if ($language) {
            $qb->join('a.language', 'l');
            $qb->andWhere('LOWER(l.value) LIKE LOWER(:language)')
                ->setParameter('language', '%' . $language->value . '%');
        }

        if ($type) {
            $qb->andWhere('a.title = :type')
                ->setParameter('type', $type);
        }

        if ($from) {
            $qb->andWhere('a.startsAt >= :from')
                ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('a.startsAt <= :to')
                ->setParameter('to', $to);
        }

        return $qb->getQuery()->getResult();
    }

    public function canAccess(Registration $registration, Student $student): bool
    {
        foreach ($registration->registeredStudents as $registeredStudent) {
            if ($registeredStudent->student->id === $student->id) {
                return true;
            }
        }

        return false;
    }
}
