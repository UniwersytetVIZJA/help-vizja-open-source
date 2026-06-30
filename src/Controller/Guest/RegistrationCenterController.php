<?php

namespace App\Controller\Guest;

use App\Database\Repository\OfficeRegistrationRegisteredStudentRepository;
use App\Database\Repository\OfficeRegistrationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RegistrationCenterController extends AbstractController
{
    public function __construct(private readonly OfficeRegistrationRepository $officeRegistrationRepository, private readonly OfficeRegistrationRegisteredStudentRepository $officeRegistrationRegisteredStudentRepository) {}

    #[Route('/gosc/centrum-zapisow', name: 'guest_registration_center_index')]
    public function index(Request $request): Response
    {
        return $this->render('guest/registration-center/index.html.twig');
    }

    #[Route('/gosc/centrum-zapisow/moje-wizyty', name: 'guest_registration_center_my_registrations')]
    #[IsGranted('ROLE_GOSC')]
    public function myRegistrations(Request $request): Response
    {
        $user = $this->getUser();

        $registrations = $this->officeRegistrationRegisteredStudentRepository->findByStudent($user);

        $now = new \DateTimeImmutable();
        $cancelMap = [];

        foreach ($registrations as $registration) {
            $term = $registration->registration->startAt;

            $allowed = false;

            if ($term instanceof \DateTimeInterface && $registration->confirmed !== false) {
                $deadline = \DateTimeImmutable::createFromInterface($term)->modify('-24 hours');
                $allowed = $now < $deadline;
            }

            $cancelMap[$registration->id] = $allowed;
        }

        return $this->render('guest/registration-center/my-registrations.html.twig', [
            'registrations' => $registrations,
            'canCancel' => $cancelMap,
        ]);
    }

    #[Route('/gosc/centrum-zapisow/moje-zapisy/archiwum', name: 'guest_registration_center_my_registrations_archive')]
    #[IsGranted('ROLE_GOSC')]
    public function archiveRegistrations(Request $request): Response
    {
        $user = $this->getUser();
        $registrations = $this->officeRegistrationRepository->findByStudent($user);

        $registrationSort = $this->officeRegistrationRepository->findArchive($user);

        $now = new \DateTimeImmutable();
        $cancelMap = [];

        foreach ($registrations as $registration) {
            $term = $registration->startAt;

            $allowed = false;

            if ($term instanceof \DateTimeInterface) {
                $deadline = \DateTimeImmutable::createFromInterface($term)->modify('-24 hours');
                $allowed = $now < $deadline;
            }

            $cancelMap[$registration->id] = $allowed;
        }

        return $this->render('guest/registration-center/archive-registrations.twig', [
            'registrations' => $registrationSort,
            'canCancell' => $cancelMap,
        ]);
    }
}
