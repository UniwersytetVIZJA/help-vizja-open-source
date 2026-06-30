<?php

namespace App\Controller\Student;

use App\Core\Student\StudentManager;
use App\Verbis\API\PobierzNrAlbumu;
use App\Verbis\API\PobierzOsobe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class VerbisController extends AbstractController
{

    public function __construct(private readonly StudentManager $studentManager, private readonly PobierzOsobe $verbisService, private readonly PobierzOsobe $pobierzOsobe) {}

    #[Route('/zaktualizuj-dane-verbis', name: 'student_profile_update_data_verbis')]
    #[IsGranted('ROLE_STUDENT')]
    public function updateDataViaVerbis(Request $request): Response
    {
        $student = $this->getUser();

        $this->studentManager->updateDataViaVerbis($student);

        return $this->redirect(
            $request->headers->get('referer') ?? $this->generateUrl('student_dashboard')
        );
    }
}
