<?php

namespace App\Controller\Guest;

use App\Core\Application\ApplicationRepository;
use App\Database\Repository\AnnouncementsRepository;
use App\Verbis\VerbisService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly ApplicationRepository $applicationRepository,
        private readonly AnnouncementsRepository $announcementsRepository,
        private readonly VerbisService $verbisService,
        private readonly RequestStack $requestStack,
    ) {}

    #[Route('/gosc', name: 'guest_dashboard')]
    public function index(): Response
    {
        return $this->render('guest/dashboard/index.html.twig', [
        ]);
    }

}
