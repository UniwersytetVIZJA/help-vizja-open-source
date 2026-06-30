<?php

namespace App\Controller\Admin;

use App\Core\Announcement\AnnouncementManager;
use App\Core\Student\StudentManager;
use App\Database\Entity\Student;
use App\Database\Repository\AnnouncementsRepository;
use App\Verbis\API\PobierzOsobe;
use App\Verbis\VerbisService;
use Doctrine\ORM\EntityManagerInterface;
use Exercise\HTMLPurifierBundle\HTMLPurifiersRegistryInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function dump;

class VerbisController extends AbstractController
{
    public function __construct(
        private readonly AnnouncementsRepository $announcementsRepository, private readonly AnnouncementManager $announcementManager,
        private readonly HTMLPurifiersRegistryInterface $purifier, private readonly EntityManagerInterface $entityManager, private readonly PaginatorInterface $paginator, private readonly VerbisService $verbisService, private readonly StudentManager $studentManager, private readonly PobierzOsobe $pobierzOsobe
    ) {}

    #[Route('/admin/verbis/{id}', name: 'admin_verbis', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function verbis(Student $id, Request $request): Response
    {
        $verbis = $this->pobierzOsobe->pobierzOrzeczenie($id);
        dump($verbis);
        exit;
    }

    #[Route('/admin/zaktualizuj-dane-verbis/{id}', name: 'admin_student_profile_update_data_verbis')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function updateDataViaVerbis(Request $request, Student $student): Response
    {
        $this->studentManager->updateDataViaVerbisAdmin($student);

        return $this->redirect(
            $request->headers->get('referer') ?? $this->generateUrl('admin_dashboard')
        );
    }

    #[Route('/admin/zaktualizuj-orzeczenie-verbis/{id}', name: 'admin_student_profile_update_disability_verbis')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function updateDisabilityVerbis(Request $request, Student $student): Response
    {
        $this->studentManager->orzeczenieVerbisAdmin($student);

        return $this->redirect(
            $request->headers->get('referer') ?? $this->generateUrl('admin_dashboard')
        );
    }
}
