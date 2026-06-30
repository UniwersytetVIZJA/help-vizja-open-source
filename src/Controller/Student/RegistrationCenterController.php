<?php

namespace App\Controller\Student;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationCenterController extends AbstractController
{
    public function __construct() {}

    #[Route('/centrum-zapisow', name: 'student_registration_center_index')]
    public function index(): Response
    {
        return $this->render('student/registration-center/index.html.twig');
    }

    #[Route('/moje-zapisy', name: 'student_registration_center_my_registrations_index')]
    public function studentRegistrations(): Response
    {
        return $this->render('student/registration-center/my-registrations.html.twig');
    }

}
