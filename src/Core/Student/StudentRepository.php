<?php

declare(strict_types=1);

namespace App\Core\Student;

use App\Database\Entity\Student;
use App\Database\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class UserRepository
 * @package App\Core\User
 */
class StudentRepository extends ServiceEntityRepository
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * UserRepository constructor
     * @param ManagerRegistry $managerRegistry
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ManagerRegistry $managerRegistry, EntityManagerInterface $entityManager)
    {
        parent::__construct($managerRegistry, Student::class);
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

        $queryBuilder->from(Student::class, 'student')
            ->select('students')
            ->addOrderBy('students.lastName')
            ->addOrderBy('students.firstName');

        return $queryBuilder;
    }

    /**
     * @param string $email
     * @return User|null
     */
    public function getOneByEmail(string $email): ?Student
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $queryBuilder
            ->select('student')
            ->from(Student::class, 'student')
            ->andWhere('student.email = :email')
            ->setParameter('email', $email);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $studentId
     * @return User|null
     */
    public function getOneById(string $studentId): ?Student
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $queryBuilder->select('s')
            ->from(Student::class, 's')
            ->andWhere('s.id = :studentId')
            ->setParameter('studentId', $studentId);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $id
     * @return int|null
     */
    public function getAlbumNumberById(string $id): ?int
    {
        return $this->createQueryBuilder('s')
            ->select('s.albumNumber')
            ->andWhere('s.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * @return array
     */
    public function getAllStudents(): array
    {
        return $this->entityManager->getRepository(Student::class)
            ->findBy([], ['lastName' => 'ASC']);
    }

    /**
     * @param string|null $student
     * @return array
     */
    public function findFilter(?string $student): array
    {
        $qb = $this->entityManager
            ->getRepository(Student::class)
            ->createQueryBuilder('u')
            ->orderBy('u.lastName', 'ASC');

        if ($student !== null && $student !== '') {
            $qb
                ->andWhere('u.email LIKE :user OR u.firstName LIKE :user OR u.lastName LIKE :user')
                ->setParameter('user', '%' . $student . '%');
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function hasAllVerbisData(string $studentId): bool
    {
        $count = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.id = :id')

            ->andWhere('a.wydzialVerbis IS NOT NULL')
            ->andWhere("a.wydzialVerbis <> ''")

            ->andWhere('a.kierunekVerbis IS NOT NULL')
            ->andWhere("a.kierunekVerbis <> ''")

            ->andWhere('a.albumNumber IS NOT NULL')

            ->andWhere('a.trybZajecVerbis IS NOT NULL')
            ->andWhere("a.trybZajecVerbis <> ''")

            ->andWhere('a.rokStudiowVerbis IS NOT NULL')
            ->andWhere("a.rokStudiowVerbis <> ''")

            ->andWhere('a.semestrVerbis IS NOT NULL')
            ->andWhere("a.semestrVerbis <> ''")

            ->setParameter('id', $studentId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }
}
