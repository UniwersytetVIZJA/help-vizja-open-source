<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WebsiteMapController extends AbstractController
{

    public function __construct() {}

    #[Route('/gosc/mapa-strony', name: 'guest_website_map')]
    public function index(): Response
    {
        return $this->render('guest/website-map/index.html.twig', [
        ]);
    }
}
