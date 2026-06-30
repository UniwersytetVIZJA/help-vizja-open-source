<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LicenseController extends AbstractController {
    public function __construct(){}

    #[Route('/gosc/licencja', name: 'guest_license_index')]
    public function index(): Response
    {
      return $this->render('guest/license/index.html.twig');
    }
}
