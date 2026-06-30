<?php

namespace App\Graph;

use App\Security\MyAzureAuthenticator;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class GraphPhotoService
{
    /**
     * @param HttpClientInterface $http
     * @param GraphTokenSession $graphTokenSession
     * @param MyAzureAuthenticator $azureAuthenticator
     * @param MyAzureAuthenticator $myAzureAuthenticator
     * @param GraphTokenManager $graphTokenManager
     */
    public function __construct(
        private HttpClientInterface $http,
        private GraphTokenSession $graphTokenSession,
        private readonly MyAzureAuthenticator $azureAuthenticator, private MyAzureAuthenticator $myAzureAuthenticator, private GraphTokenManager $graphTokenManager,
    ) {}

    /**
     * Pobiera zdjęcie profilowe aktualnie zalogowanego użytkownika z Microsoft Graph.
     *
     * Metoda wykonuje żądanie do API Microsoft Graph (`/me/photo/$value`)
     * i zwraca binarną zawartość zdjęcia wraz z typem MIME.
     * Jeśli użytkownik nie posiada zdjęcia lub API zwróci inny kod niż 200,
     * metoda zwraca `null`.
     *
     * @return array|null Tablica z kluczami:
     *                    - content (string) – binarna zawartość obrazu
     *                    - content_type (string) – typ MIME obrazu
     *
     * @throws TransportExceptionInterface    Błąd transportu HTTP
     * @throws ServerExceptionInterface       Błąd po stronie serwera Microsoft Graph
     * @throws RedirectionExceptionInterface  Błąd przekierowania
     * @throws ClientExceptionInterface       Błąd żądania klienta
     */
    public function getMyPhoto(): ?array
    {
        $token = $this->graphTokenManager->getValidAccessToken(GraphEnum::CLIENT_STUDENT->value); //azure_admin

        $response = $this->http->request(
            'GET',
            'https://graph.microsoft.com/v1.0/me/photo/$value',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'image/*',
                ],
            ]
        );

        $status = $response->getStatusCode();
        $body = $response->getContent(false);

        if ($status !== 200 || !$body) {
            return null;
        }

        $headers = $response->getHeaders(false);
        $contentType = $headers['content-type'][0] ?? 'image/jpeg';

        return [
            'content' => $body,
            'content_type' => $contentType,
        ];
    }

    /**
     * Pobiera zdjęcie profilowe wskazanego użytkownika z Microsoft Graph.
     *
     * Metoda wykonuje żądanie do API Microsoft Graph (`/users/{upn}/photo/$value`)
     * i zwraca binarną zawartość zdjęcia wraz z typem MIME.
     * Jeśli użytkownik nie posiada zdjęcia lub API zwróci inny kod niż 200,
     * metoda zwraca `null`.
     *
     * @param string $userUpn User Principal Name użytkownika (np. adres e-mail)
     *
     * @return array|null Tablica z kluczami:
     *                    - content (string) – binarna zawartość obrazu
     *                    - content_type (string) – typ MIME obrazu
     *
     * @throws TransportExceptionInterface    Błąd transportu HTTP
     * @throws ServerExceptionInterface       Błąd po stronie serwera Microsoft Graph
     * @throws RedirectionExceptionInterface  Błąd przekierowania
     * @throws ClientExceptionInterface       Błąd żądania klienta
     */
    public function getUserPhoto(string $userUpn): ?array
    {
        $token = $this->graphTokenManager->getValidAccessToken(GraphEnum::CLIENT_ADMIN->value);

        $response = $this->http->request(
            'GET',
            sprintf(
                'https://graph.microsoft.com/v1.0/users/%s/photo/$value',
                rawurlencode($userUpn)
            ),
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'image/*',
                ],
            ]
        );

        $status = $response->getStatusCode();
        $body = $response->getContent(false);

        if ($status !== 200 || !$body) {
            return null;
        }

        $headers = $response->getHeaders(false);
        $contentType = $headers['content-type'][0] ?? 'image/jpeg';

        return [
            'content' => $body,
            'content_type' => $contentType,
        ];
    }

}
