<?php

declare(strict_types=1);

namespace App\Core\Application;

use App\Database\Entity\Application;
use App\Database\Entity\Application\EducationalProcess;
use App\Database\Entity\Application\LanguageInterpreter;
use App\Database\Entity\Application\SpecialisedEquipment;
use App\Database\Entity\Application\TeachingAssistant;
use App\Database\Entity\Student;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use function explode;
use function sprintf;

/**
 * Class ApplicationRepository
 * @package App\Core\Application
 */
class ApplicationRepository extends ServiceEntityRepository
{
    /**
     * ApplicationRepository constructor
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Application::class);
    }

    /**
     * @param string $applicationId
     * @return Application|null
     */
    public function findOneById(string $applicationId): ?Application
    {
        return $this->find($applicationId);
    }

    /**
     * @param Student $student
     * @return Application|null
     */
    public function findOneByStudent(Student $student): ?Application
    {
        return $this->createQueryBuilder('a')
            ->where('a.student = :student')
            ->setParameter('student', $student)
            ->orderBy('a.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $studentId
     * @return array
     */
    public function findByStudent(string $studentId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.type IS NOT NULL')
            ->andWhere('s.studyMode IS NOT NULL')
            ->andWhere('s.faculty IS NOT NULL')
            ->andWhere('s.year IS NOT NULL')
            ->andWhere('s.student IS NOT NULL')
            ->andWhere('s.phone IS NOT NULL')
            ->andWhere('s.albumNumber IS NOT NULL')
            ->andWhere('s.applicationNumber IS NOT NULL')
            ->andWhere('s.student = :studentObject')
            ->setParameter('studentObject', $studentId)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $studentId
     * @return object|null
     */
    public function findLatest(string $studentId): ?object
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.student', 's')
            ->addSelect('s')
            ->andWhere('s.id = :sid')
            ->andWhere('a.type IS NOT NULL')
            ->andWhere('a.studyMode IS NOT NULL')
            ->andWhere('a.faculty IS NOT NULL')
            ->andWhere('a.year IS NOT NULL')
            ->andWhere('a.phone IS NOT NULL')
            ->andWhere('a.albumNumber IS NOT NULL')
            ->setParameter('sid', $studentId)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $studentId
     * @return array
     */
    public function findLatestProfile(string $studentId): array
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.student', 's')
            ->addSelect('s')
            ->andWhere('s.id = :sid')
            ->andWhere('a.type IS NOT NULL')
            ->andWhere('a.studyMode IS NOT NULL')
            ->andWhere('a.faculty IS NOT NULL')
            ->andWhere('a.year IS NOT NULL')
            ->andWhere('a.phone IS NOT NULL')
            ->andWhere('a.albumNumber IS NOT NULL')
            ->setParameter('sid', $studentId)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array
     */
    public function findAllNotNull(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.createdAt', 'DESC')
            ->andWhere('e.type IS NOT NULL')
            ->andWhere('e.studyMode IS NOT NULL')
            ->andWhere('e.faculty IS NOT NULL')
            ->andWhere('e.year IS NOT NULL')
            ->andWhere('e.student IS NOT NULL')
            ->andWhere('e.phone IS NOT NULL')
            ->andWhere('e.albumNumber IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string|null $type
     * @param string|null $student
     * @param string|null $status
     * @param \DateTimeImmutable|null $from
     * @param \DateTimeImmutable|null $to
     * @return array
     */
    public function findFilter(?string $type, ?string $student, ?string $status, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.student', 's')
            ->addSelect('s')
            ->orderBy('a.createdAt', 'DESC')
            ->andWhere('a.type IS NOT NULL')
            ->andWhere('a.studyMode IS NOT NULL')
            ->andWhere('a.faculty IS NOT NULL')
            ->andWhere('a.year IS NOT NULL')
            ->andWhere('a.student IS NOT NULL')
            ->andWhere('a.phone IS NOT NULL')
            ->andWhere('a.albumNumber IS NOT NULL')
            ->andWhere('a.applicationNumber IS NOT NULL');

        if ($type && $type !== 'all') {
            $class = match ($type) {
                'educational_process' => EducationalProcess::class,
                'language_interpreter' => LanguageInterpreter::class,
                'specialised_equipment' => SpecialisedEquipment::class,
                'teaching_assistant' => TeachingAssistant::class,
                default => null,
            };

            if ($class !== null) {
                $qb->andWhere('a INSTANCE OF ' . $class);
            }
        }

        if ($student) {
            $qb->andWhere('a.albumNumber LIKE :student OR s.firstName LIKE :student OR s.lastName LIKE :student')
                ->setParameter('student', '%' . $student . '%');
        }

        if ($status) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $status);
        }

        if ($from) {
            $qb->andWhere('a.createdAt >= :from')
                ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('a.createdAt <= :to')
                ->setParameter('to', $to);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $studentId
     * @return int
     */
    public function countByStudent(string $studentId): int
    {
        return (int)$this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->innerJoin('a.student', 's')
            ->where('s.id = :studentId')
            ->andWhere('a.type IS NOT NULL')
            ->andWhere('a.studyMode IS NOT NULL')
            ->andWhere('a.faculty IS NOT NULL')
            ->andWhere('a.year IS NOT NULL')
            ->andWhere('a.phone IS NOT NULL')
            ->andWhere('a.albumNumber IS NOT NULL')
            ->setParameter('studentId', $studentId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param Student $student
     * @param Application $application
     * @return void
     */
    public function getInfoFromStudent(Student $student, Application $application): void
    {
        if ($application->albumNumber === null && $student->albumNumber !== null) {
            $application->albumNumber = $student->albumNumber;
        }

        if ($application->phone === null && $student->phone !== null) {
            $application->phone = $student->phone;
        }

        if ($application->faculty === null && $student->faculty !== null) {
            $application->faculty = $student->faculty;
        }

        if ($application->studyMode === null && $student->studyMode !== null) {
            $application->studyMode = $student->studyMode;
        }

        if ($application->year === null && $student->studyYear !== null) {
            $application->year = $student->studyYear;
        }

        if ($student->studySemester === null && $student->studySemester !== null) {
            $application->semester = $student->studySemester;
        }
    }

    /**
     * @param string $prefix
     * @return string
     */
    public function generateApplicationNumber(string $prefix): string
    {
        $year = new \DateTimeImmutable()->format('Y');

        $last = $this->createQueryBuilder('a')
            ->select('a.applicationNumber')
            ->where('a.applicationNumber LIKE :prefix')
            ->setParameter('prefix', $prefix . '-' . $year . '-%')
            ->orderBy('a.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$last || empty($last['applicationNumber'])) {
            return sprintf('%s-%s-1', $prefix, $year);
        }

        [, , $number] = explode('-', $last['applicationNumber']);

        return sprintf('%s-%s-%d', $prefix, $year, ((int)$number + 1));
    }

    /**
     * @param Student $student
     * @return bool
     */
    public function hasUnreadComments(Student $student): bool
    {
        $count = (int)$this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.student = :student')
            ->andWhere('a.employeeCommentDate IS NOT NULL')
            ->andWhere('(a.applicationDetailsSeen IS NULL OR a.employeeCommentDate > a.applicationDetailsSeen)')
            ->setParameter('student', $student)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
