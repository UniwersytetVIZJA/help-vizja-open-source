<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LicenseController extends AbstractController {
    public function __construct(){}

    #[Route('/admin/licencja', name: 'admin_license_index')]
    public function index(): Response
    {
      return $this->render('admin/license/index.html.twig');
    }
}
