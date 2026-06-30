<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Database\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class DashboardController
 * @package App\Controller\Admin
 */
class DashboardController extends AbstractController
{
    /**
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $test = $entityManager->getRepository(User::class)->findAll();

        $categories = [
            [
                'name' => 'Wizyta 1',
                'url' => '#',
            ],
            [
                'name' => 'Wizyta 2',
                'url' => '#',
            ],
        ];

        return $this->render('admin/dashboard/index.html.twig', [
            'categories' => $categories
        ]);
    }
}
