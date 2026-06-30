<?php
declare(strict_types=1);

namespace App\Controller\Student;

use App\Database\Entity\Student;
use App\Verbis\API\PobierzOsobe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use function dump;
use function in_array;
use function str_ends_with;
use function strtolower;

class SecurityController extends AbstractController
{
    public function __construct(private readonly PobierzOsobe $pobierzOsobe) {}

    /**
     * Wyświetla formularz logowania dla studenta.
     *
     * Jeśli użytkownik jest już zalogowany, następuje przekierowanie
     * do panelu studenta.
     *
     * @param AuthenticationUtils $authenticationUtils Narzędzie pomocnicze do obsługi logowania
     *
     * @return Response|\Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    #[Route('/zaloguj', name: 'student_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request)
    {
        $student = $this->getUser();

        if ($student instanceof Student && in_array('ROLE_GOSC', $student->getRoles(), true)) {
            return $this->redirectToRoute('guest_dashboard');
        }

        if($student){
            return $this->redirectToRoute('student_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($lastUsername && str_ends_with(strtolower($lastUsername), '@students.vizja.pl') || str_ends_with(strtolower($lastUsername), '@vizja.pl')) {
            $error = new CustomUserMessageAuthenticationException(
                'Próbujesz zalogować się kontem uczelni. Wybierz opcję "Zaloguj się kontem Office 365"'
            );
        }

        return $this->render('student/security/login2.html.twig', [
            'error' => $error,
            'last_username' => $lastUsername,
        ]);
    }

}
