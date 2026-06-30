<?php

namespace App\Controller\Guest;

use App\Database\Repository\FaqRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FaqController extends AbstractController
{
    public function __construct(private readonly FaqRepository $faqRepository) {}

    #[Route('/gosc/faq', name: 'guest_faq')]
    public function index(Request $request): Response
    {
        $faq = $this->faqRepository->findAll();

        return $this->render('guest/faq/index.html.twig', [
            'faq' => $faq,
        ]);
    }
}
