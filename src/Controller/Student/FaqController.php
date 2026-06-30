<?php

namespace App\Controller\Student;

use App\Database\Repository\FaqRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FaqController extends AbstractController
{
    public function __construct(private readonly FaqRepository $faqRepository) {}

    #[Route('/faq', name: 'student_faq')]
    public function index(Request $request): Response
    {
        $faq = $this->faqRepository->findAll();

        return $this->render('student/faq/faq.html.twig', [
            'faq' => $faq,
        ]);
    }

}
