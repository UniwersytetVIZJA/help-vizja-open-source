<?php

namespace App\Controller\Guest;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class InformationAcceptController extends AbstractController {
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    #[Route('/gosc/guest-recommended-website-accept', name: 'guest_recommended_website_accept', methods: ['POST'])]
    public function website(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if(!$user){
            throw $this->createNotFoundException('Nie znaleziono użytkownika');
        }

        if (!$this->isCsrfTokenValid('recommended_website_accept', $request->request->get('_token'))) {
            return new JsonResponse(['success' => false], 403);
        }

        if($user->recommendedWebsite === null){
            $user->recommendedWebsite = new \DateTimeImmutable();
            $this->entityManager->flush();
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route('/gosc/guest-cookies-accept', name: 'guest_cookies_accept', methods: ['POST'])]
    public function cookies(Request $request): JsonResponse
    {
        $student = $this->getUser();

        if(!$student){
            throw $this->createNotFoundException('Nie znaleziono użytkownika');
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
