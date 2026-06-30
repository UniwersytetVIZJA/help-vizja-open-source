<?php

declare(strict_types=1);

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class SecurityController
 * @package App\Controller\Guest
 */
class SecurityController extends AbstractController
{
    /**
     * @return void
     */
    #[Route('/wyloguj', name: 'guest_logout')]
    public function logout(): void
    {}
}
