<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SettingsController extends AbstractController
{
    public function __construct() {}

    /**
     * Wyświetla stronę ustawień w panelu administracyjnym.
     *
     * @return Response Widok ustawień
     */
    #[Route('/admin/ustawienia', name: 'admin_settings')]
    public function index(): Response
    {
        return $this->render('admin/settings/index.html.twig');
    }
}
