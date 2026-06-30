<?php

namespace App\TwigHelper\GlobalExtension;

use App\Database\Entity\Student;
use App\Database\Repository\AnnouncementsRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class NewAnnouncement extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly AnnouncementsRepository $announcementRepository,
    ) {}

    public function getGlobals(): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof Student) {
            return ['hasNewAnnouncements' => false];
        }

        $seen = $user->announcementSeen;
        $latest = $this->announcementRepository->getLatestStartsAt();
        $count = $this->announcementRepository->countUnreadMessages();

        return [
            'hasNewAnnouncements' => $latest && $latest > $seen,
            'count' => $count,
        ];
    }
}
