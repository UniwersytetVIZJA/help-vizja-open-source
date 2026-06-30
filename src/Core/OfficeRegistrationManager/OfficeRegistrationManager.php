<?php

namespace App\Core\OfficeRegistrationManager;

use App\Core\BaseManager;
use App\Database\Entity\OfficeRegistration;
use App\Database\Repository\OfficeRegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use function method_exists;
use function property_exists;

class OfficeRegistrationManager extends BaseManager
{
    private const string SESSION_ID = 'registration_office_id';

    public function __construct(private readonly RequestStack $requestStack, private readonly OfficeRegistrationRepository $officeRegistrationRepository, private readonly Security $security, private readonly EntityManagerInterface $entityManager) {}

    public function getOfficeRegistrationFromSession(): OfficeRegistration
    {
        $session = $this->requestStack->getSession();
        $id = $session->get(self::SESSION_ID);

        if (!$id) {
            throw new \LogicException('Brak wniosku w sesji, utwórz nowy wniosek');
        }

        $application = $this->officeRegistrationRepository->find($id);
        if (!$application instanceof OfficeRegistration) {
            throw new \LogicException('Wniosek z zapisanym identyfikatorem nie istnieje.');
        }

        return $application;
    }

    public function createSession(OfficeRegistration $officeRegistration): void
    {
        $session = $this->requestStack->getSession();
        $session->set(self::SESSION_ID, $officeRegistration->getId());
    }

    public function update(OfficeRegistration $officeRegistration): void
    {
        $this->basePersister->update($officeRegistration, true);
    }

    public function delete(OfficeRegistration $officeRegistration, bool $flush = true): void
    {
        $this->entityManager->remove($officeRegistration);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function updateTeams(OfficeRegistration $registration, ?string $joinUrl, ?string $eventId, bool $flush = true): void
    {
        if (method_exists($registration, 'setTeamsJoinUrl')) {
            $registration->setTeamsJoinUrl($joinUrl);
        } else if (property_exists($registration, 'teamsMeetingUrl')) {
            $registration->teamsMeetingUrl = $joinUrl;
            $registration->eventId = $eventId;
        } else {
            throw new \LogicException('Registration nie posiada setTeamsJoinUrl() ani pola teamsMeetingUrl.');
        }
        $this->entityManager->persist($registration);

        if ($flush) {
            $this->entityManager->flush();
        }
    }
}
