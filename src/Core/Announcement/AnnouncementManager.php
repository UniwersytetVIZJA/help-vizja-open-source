<?php

namespace App\Core\Announcement;

use App\Core\BaseManager;
use App\Database\Entity\Announcements;
use Doctrine\ORM\EntityManagerInterface;

class AnnouncementManager extends BaseManager
{

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * @param Announcements $announcements
     * @return void
     */
    public function createAnnouncement(Announcements $announcements): void
    {
        $this->entityManager->persist($announcements);
        $this->entityManager->flush();
    }
}
