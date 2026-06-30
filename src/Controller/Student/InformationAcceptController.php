<?php

namespace App\Controller\Student;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class InformationAcceptController extends AbstractController {
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    #[Route('/student-recommended-website-accept', name: 'student_recommended_website_accept', methods: ['POST'])]
    public function website(Request $request): JsonResponse
    {
        $student = $this->getUser();

        if(!$student){
            throw $this->createNotFoundException('Nie znaleziono studenta');
        }

        if (!$this->isCsrfTokenValid('recommended_website_accept', $request->request->get('_token'))) {
            return new JsonResponse(['success' => false], 403);
        }

        if($student->recommendedWebsite === null){
            $student->recommendedWebsite = new \DateTimeImmutable();
            $this->entityManager->flush();
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route('/student-cookies-accept', name: 'student_cookies_accept', methods: ['POST'])]
    public function cookies(Request $request): JsonResponse
    {
        $student = $this->getUser();

        if(!$student){
            throw $this->createNotFoundException('Nie znaleziono studenta');
        }

        if (!$this->isCsrfTokenValid('cookies_accept', $request->request->get('_token'))) {
            return new JsonResponse(['success' => false], 403);
        }

        if($student->cookiesAccept === null){
            $student->cookiesAccept = new \DateTimeImmutable();
            $this->entityManager->flush();
        }

        return new JsonResponse(['success' => true]);
    }
}
