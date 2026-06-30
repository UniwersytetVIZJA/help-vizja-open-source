<?php

namespace App\Controller\Student;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccesibilityStatementController extends AbstractController
{

    public function __construct() {}

    #[Route('/deklaracja-dostepnosci', name: 'app_student_accesibilitystatement_index')]
    public function index(Request $request): Response
    {
        $template = $request->getLocale() === 'pl' ? 'student/accessibility-statement/index.html.twig' : 'student/accessibility-statement/en/index.html.twig';

        return $this->render($template);
    }
}
