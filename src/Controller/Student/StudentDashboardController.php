<?php

namespace App\Controller\Student;

use App\Core\Application\ApplicationRepository;
use App\Database\Entity\Student;
use App\Database\Repository\AnnouncementsRepository;
use App\Verbis\VerbisService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use function in_array;

class StudentDashboardController extends AbstractController
{
    /**
     * @param ApplicationRepository $applicationRepository
     * @param AnnouncementsRepository $announcementsRepository
     * @param VerbisService $verbisService
     * @param RequestStack $requestStack
     */
    public function __construct(
        private readonly ApplicationRepository $applicationRepository,
        private readonly AnnouncementsRepository $announcementsRepository,
        private readonly VerbisService $verbisService,
        private readonly RequestStack $requestStack,
    ) {}

    /**
     * Wyświetla panel główny studenta.
     *
     * @return Response Widok panelu głównego studenta
     */
    #[Route('/', name: 'student_dashboard')]
    public function index(): Response
    {
        if ($this->getUser() instanceof Student && in_array('ROLE_GOSC', $this->getUser()->getRoles(), true)) {
            return $this->redirectToRoute('guest_dashboard');
        }

        $studentId = $this->getUser()->getUserIdentifier();
        $latest = $this->applicationRepository->findLatest($studentId);
        $application = $this->applicationRepository->findAll();

        $latestAnnouncements = $this->announcementsRepository->findLatest();

        return $this->render('student/dashboard/dashboard.html.twig', [
            'title' => 'Panel użytkownika',
            'applications' => $application,
            'latestApplication' => $latest,
            'latestAnnouncement' => $latestAnnouncements,
        ]);
    }
}
