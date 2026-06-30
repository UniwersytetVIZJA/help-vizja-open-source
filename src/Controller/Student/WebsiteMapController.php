<?php

namespace App\Controller\Student;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WebsiteMapController extends AbstractController
{

    public function __construct() {}

    #[Route('/mapa-strony', name: 'student_website_map')]
    public function index(): Response
    {
        return $this->render('student/website-map/index.html.twig', [
        ]);
    }
}
