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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class AzureController
 * @package App\Controller
 */
class AdminAzureController extends AbstractController
{
    /**
     * @param GraphPhotoService $graphPhotoService
     */
    public function __construct(private readonly GraphPhotoService $graphPhotoService, private readonly LoggerInterface $logger) {}

    /**
     * Inicjuje proces logowania administratora przez Azure Active Directory.
     *
     * Metoda przekierowuje użytkownika do dostawcy uwierzytelniania Azure AD
     * w celu rozpoczęcia procesu logowania.
     *
     * @param ClientRegistry $clientRegistry Rejestr klientów OAuth
     *
     * @return RedirectResponse Przekierowanie do Azure AD
     */
    #[Route('/admin/security/azure/login', name: 'admin_security_azure_login')]
    public function login(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('azure_admin')
            ->redirect([
                'email',
                'offline_access',
                'openid',
                'profile',
                'User.Read.All',
                'ProfilePhoto.Read.All',
            ]);
    }

    /**
     * Punkt kontrolny uwierzytelniania Azure AD (callback).
     *
     * Metoda wykorzystywana przez mechanizm logowania OAuth2
     * do finalizacji procesu uwierzytelniania administratora.
     *
     * @param ClientRegistry $clientRegistry Rejestr klientów OAuth
     *
     * @return void
     */
    #[Route('/admin/security/azure/check', name: 'admin_security_azure_check')]
    public function check(ClientRegistry $clientRegistry): void
    {
        $client = $clientRegistry->getClient('azure_admin');

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

    #[Route('/admin/security/azure/error', name: 'admin_security_azure_error')]
    public function error(Request $request): Response
    {
        $message = $request->getSession()->get('azure_error');

        if (!$message) {
            return $this->redirectToRoute('admin_security_login');
        }

        $request->getSession()->remove('azure_error');

        return $this->render('error/AdminAzureCheck.html.twig', [
            'message' => $message,
        ]);
    }

    /**
     * Zwraca avatar aktualnie zalogowanego użytkownika z Microsoft Graph.
     *
     * Metoda pobiera zdjęcie profilowe użytkownika i zwraca je jako odpowiedź HTTP.
     * W przypadku braku zalogowanego użytkownika zwracany jest kod 401,
     * a gdy użytkownik nie posiada avatara – kod 204 (No Content).
     *
     * @return Response Odpowiedź zawierająca avatar użytkownika lub kod statusu
     *
     * @throws TransportExceptionInterface   Błąd transportu HTTP
     * @throws ServerExceptionInterface     Błąd po stronie serwera zewnętrznego
     * @throws RedirectionExceptionInterface Błąd przekierowania
     * @throws ClientExceptionInterface     Błąd żądania klienta
     */
    #[Route('/admin/user/avatar', name: 'admin_user_ms_avatar')]
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

    /**
     * Zwraca avatar wskazanego użytkownika z Microsoft Graph.
     *
     * Metoda pobiera zdjęcie profilowe użytkownika na podstawie jego UPN
     * (User Principal Name) i zwraca je jako odpowiedź HTTP.
     * W przypadku braku avatara zwracany jest kod 204 (No Content).
     *
     * @param string $upn User Principal Name użytkownika (np. adres e-mail)
     *
     * @return Response Odpowiedź zawierająca avatar użytkownika lub kod 204
     *
     * @throws TransportExceptionInterface    Błąd transportu HTTP
     * @throws ServerExceptionInterface       Błąd po stronie serwera zewnętrznego
     * @throws RedirectionExceptionInterface  Błąd przekierowania
     * @throws ClientExceptionInterface       Błąd żądania klienta
     */
    #[Route('/admin/user/{upn}/avatar', name: 'admin_ms_user_photo')]
    public function userPhoto(string $upn): Response
    {
        $photo = $this->graphPhotoService->getUserPhoto($upn);
        if (!$photo) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return new Response($photo['content'], 200, [
            'Content-Type' => $photo['content_type'],
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    #[Route('/user/{upn}/avatar', name: 'student_ms_user_photo')]
    public function studentUserPhoto(string $upn): Response
    {
        $photo = $this->graphPhotoService->getUserPhoto($upn);

        if (!$photo) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return new Response($photo['content'], Response::HTTP_OK, [
            'Content-Type' => $photo['content_type'],
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
