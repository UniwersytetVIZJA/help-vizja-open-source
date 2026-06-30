<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Database\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Class SecurityController
 * @package App\Controller\Admin
 */
class SecurityController extends AbstractController
{
    /**
     * Wyświetla formularz logowania do panelu administracyjnego.
     *
     * Jeśli użytkownik jest już zalogowany, następuje przekierowanie
     * do panelu głównego.
     *
     * @param AuthenticationUtils $authenticationUtils Narzędzie pomocnicze do obsługi logowania
     * @param EntityManagerInterface $en Menedżer encji (wstrzykiwany przez kontener)
     *
     * @return Response Widok formularza logowania lub przekierowanie
     */
    #[Route('/admin/zaloguj', name: 'admin_security_login')]
    public function login(AuthenticationUtils $authenticationUtils, EntityManagerInterface $en): Response
    {
        if ($this->getUser() instanceof User) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('admin/security/login2.html.twig', [
            'error' => $error,
            'last_username' => $lastUsername,
        ]);
    }

}
