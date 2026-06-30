<?php

declare(strict_types=1);

namespace App\Controller;

use App\Graph\GraphPhotoService;
use Exception;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class AzureController
 * @package App\Controller
 */
class AzureController extends AbstractController
{
    public function __construct(private readonly GraphPhotoService $graphPhotoService, private readonly LoggerInterface $logger) {}

    /**
     * Inicjuje proces logowania studenta przez Azure Active Directory.
     *
     * Metoda przekierowuje użytkownika do dostawcy uwierzytelniania Azure AD
     * w celu rozpoczęcia procesu logowania.
     *
     * @param ClientRegistry $clientRegistry Rejestr klientów OAuth
     *
     * @return RedirectResponse Przekierowanie do Azure AD
     */
    #[Route('/security/azure/login', name: 'security_azure_login')]
    public function login(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('azure_student')
            ->redirect([
                'email',
                'offline_access',
                'openid',
                'profile',
                'User.Read',
            ]);
    }

    /**
     * Punkt kontrolny uwierzytelniania Azure AD dla studentów (callback).
     *
     * Metoda wykorzystywana przez mechanizm logowania OAuth2
     * do finalizacji procesu uwierzytelniania studenta.
     *
     * @param ClientRegistry $clientRegistry Rejestr klientów OAuth
     *
     * @return void
     */
    #[Route('/security/azure/check', name: 'security_azure_check')]
    public function check(ClientRegistry $clientRegistry): void
    {
        $client = $clientRegistry->getClient('azure_student');

        try {
            $client->fetchUser();
        } catch (Exception $e) {
            $this->logger->error('Logowanie do azure nie powiodło się', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    #[Route('/security/azure/error', name: 'security_azure_error')]
    public function error(Request $request): Response
    {
        $message = $request->getSession()->get('azure_error');

        if (!$message) {
            return $this->redirectToRoute('student_login');
        }

        $request->getSession()->remove('azure_error');

        return $this->render('error/AzureCheck.html.twig', [
            'message' => $message,
        ]);
    }

    /**
     * Zwraca avatar aktualnie zalogowanego użytkownika z Microsoft Graph.
     *
     * Metoda pobiera zdjęcie profilowe zalogowanego użytkownika i zwraca je
     * jako odpowiedź HTTP. W przypadku braku zalogowanego użytkownika zwracany
     * jest kod 401, a gdy avatar nie istnieje – kod 204 (No Content).
     *
     * @return Response Odpowiedź zawierająca avatar użytkownika lub kod statusu
     *
     * @throws TransportExceptionInterface    Błąd transportu HTTP
     * @throws ServerExceptionInterface       Błąd po stronie serwera zewnętrznego
     * @throws RedirectionExceptionInterface  Błąd przekierowania
     * @throws ClientExceptionInterface       Błąd żądania klienta
     */
    #[Route('/user/avatar', name: 'user_ms_avatar')]
    public function avatar(): Response
    {
        if (!$this->getUser()) {
            return new Response('', Response::HTTP_UNAUTHORIZED);
        }

        $photo = $this->graphPhotoService->getMyPhoto();

        if ($photo === null) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return new Response($photo['content'], 200, [
            'Content-Type' => $photo['content_type'],
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

}
