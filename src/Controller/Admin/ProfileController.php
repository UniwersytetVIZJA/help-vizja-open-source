<?php

namespace App\Controller\Admin;

use App\Controller\Student\SecurityController;
use App\Core\Application\ApplicationRepository;
use App\Database\Repository\RegisteredStudentRepository;
use App\Database\Repository\RegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    /**
     * @param RegistrationRepository $meetingRepository
     * @param EntityManagerInterface $entityManager
     * @param SecurityController $securityController
     * @param RegistrationRepository $registrationRepository
     * @param RegisteredStudentRepository $registeredStudentRepository
     * @param ApplicationRepository $applicationRepository
     */
    public function __construct(
        private readonly RegistrationRepository $meetingRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SecurityController $securityController,
        private readonly RegistrationRepository $registrationRepository, private readonly RegisteredStudentRepository $registeredStudentRepository, private readonly ApplicationRepository $applicationRepository,
    ) {}

    /**
     * @return Response
     */
    #[Route('/admin/profil', name: 'admin_profile_index')]
    public function index(): Response
    {
        $studentId = $this->getUser()->getUserIdentifier();

        $latestApplication = $this->applicationRepository->findLatestProfile($studentId);
        $latestRegistration = $this->registeredStudentRepository->findNextForStudent($studentId);

        $countApplication = $this->applicationRepository->countByStudent($studentId);
        $countRegistration = $this->registeredStudentRepository->countByStudent($studentId);

        $allRegistration = $this->registrationRepository->countAllActive();

        return $this->render('admin/profile/user-profile.html.twig', [
            'latestApplication' => $latestApplication,
            'latestRegistration' => $latestRegistration,
            'countApplication' => $countApplication,
            'countRegistration' => $countRegistration,
            'allRegistration' => $allRegistration,
        ]);
    }
}
