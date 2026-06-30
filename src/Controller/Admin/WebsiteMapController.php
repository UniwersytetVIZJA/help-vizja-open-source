<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WebsiteMapController extends AbstractController
{

    public function __construct() {}

    #[Route('/admin/mapa-strony', name: 'admin_website_map')]
    public function index(): Response
    {
        return $this->render('admin/website-map/index.html.twig', [
        ]);
    }
}
