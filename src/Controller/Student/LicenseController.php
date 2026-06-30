<?php

namespace App\Controller\Student;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LicenseController extends AbstractController {
    public function __construct(){}

    #[Route('/licencja', name: 'student_license_index')]
    public function index(): Response
    {
      return $this->render('student/license/index.html.twig');
    }
}
