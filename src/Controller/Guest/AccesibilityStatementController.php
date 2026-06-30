<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccesibilityStatementController extends AbstractController
{

    public function __construct() {}

    #[Route('/gosc/deklaracja-dostepnosci', name: 'app_guest_accesibilitystatement_index')]
    public function index(Request $request): Response
    {
        $template = $request->getLocale() === 'pl' ? 'guest/accessibility-statement/index.html.twig' : 'guest/accessibility-statement/en/index.html.twig';

        return $this->render($template);
    }
}
