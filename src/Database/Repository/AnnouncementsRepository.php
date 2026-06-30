<?php

namespace App\Database\Repository;

use App\Database\Entity\Announcements;
use DateMalformedStringException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

class AnnouncementsRepository extends ServiceEntityRepository
{

    /**
     * @param ManagerRegistry $managerRegistry
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ManagerRegistry $managerRegistry, EntityManagerInterface $entityManager)
    {
        parent::__construct($managerRegistry, Announcements::class);
    }

    /**
     * @param \DateTimeImmutable|null $now
     * @return array
     */
    public function findActive(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();

        return $this->createQueryBuilder('a')
            ->andWhere('a.published = :pub')
            ->setParameter('pub', true)
            ->andWhere('a.startsAt IS NULL OR a.startsAt <= :now')
            ->andWhere('a.expiresAt IS NULL OR a.expiresAt > :now')
            ->setParameter('now', $now)
            ->orderBy('a.startsAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \DateTimeImmutable|null $now
     * @return array
     */
    public function findArchive(?\DateTimeImmutable $now = null): array
    {
        return $this->createQueryBuilder('a')
            ->orWhere('a.published = :pub')
            ->setParameter('pub', false)
            ->orWhere('a.expiresAt IS NOT NULL AND a.expiresAt < :now')
            ->setParameter('now', $now ?? new \DateTimeImmutable())
            ->orderBy('a.startsAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array
     */
    public function findLatest(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.published = true')
            ->andWhere('a.startsAt IS NULL OR a.startsAt <= :now')
            ->andWhere('a.expiresAt IS NULL OR a.expiresAt > :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.startsAt', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $id
     * @return Announcements|null
     */
    public function findOneById(string $id): ?Announcements
    {
        return $this->find($id);
    }

    /**
     * @param Announcements $announcements
     * @param Request $request
     * @return Announcements
     */
    public function announcementUpdate(Announcements $announcements, Request $request): Announcements
    {
        $announcements->description = $request->request->get('description', $announcements->description);
        $announcements->startsAt = $request->request->get('startsAt', $announcements->startsAt);
        $announcements->expiresAt = $request->request->get('expiresAt', $announcements->expiresAt);
        $announcements->title = $request->request->get('title', $announcements->title);

        $this->getEntityManager()->persist($announcements);
        $this->getEntityManager()->flush();

        return $announcements;
    }

    /**
     * @return \DateTimeImmutable|null
     * @throws DateMalformedStringException
     */
    public function getLatestStartsAt(): ?\DateTimeImmutable
    {
        $now = new \DateTimeImmutable();

        $value = $this->createQueryBuilder('a')
            ->select('MAX(a.startsAt)')
            ->andWhere('a.published = :published')
            ->andWhere('a.expiresAt >= :now')
            ->setParameter('published', true)
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();

        if (!$value) {
            return null;
        }

        return new \DateTimeImmutable($value);
    }

    public function countUnreadMessages(): int
    {
        $now = new \DateTimeImmutable();

        return (int)$this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.published = :published')
            ->andWhere('a.expiresAt >= :now')
            ->setParameter('published', true)
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
