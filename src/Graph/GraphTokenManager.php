<?php

namespace App\Graph;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use function time;

readonly class GraphTokenManager
{
    /**
     * @param GraphTokenSession $tokenSession
     * @param ClientRegistry $clientRegistry
     */
    public function __construct(
        private GraphTokenSession $tokenSession, private ClientRegistry $clientRegistry,
    ) {}

    /**
     * Zwraca ważny token dostępu (access token) dla wskazanego klienta OAuth.
     *
     * Metoda pobiera token z sesji i sprawdza jego ważność. Jeśli token wygasł,
     * automatycznie próbuje go odświeżyć przy użyciu refresh tokenu
     * i zapisuje nowy token w sesji.
     *
     * @param string $clientName Nazwa klienta OAuth (np. azure_admin, azure_student)
     *
     * @return string Aktualny, ważny access token
     *
     * @throws \RuntimeException Gdy brak tokenu w sesji lub brak refresh tokenu
     */
    public function getValidAccessToken(string $clientName): string
    {
        $accessToken = $this->tokenSession->getAccessToken();
        $expiresAt = $this->tokenSession->getExpiresAt();
        $refreshToken = $this->tokenSession->getRefreshToken();
        $now = time();

        if (!$accessToken || !$expiresAt) {
            throw new \RuntimeException('Brak tokenu w sesji – zaloguj się ponownie przez Microsoft');
        }

        if ($expiresAt <= $now) {
            if (!$refreshToken) {
                throw new \RuntimeException('Brak refresh tokenu – zaloguj się ponownie przez Microsoft.');
            }

            $client = $this->clientRegistry->getClient($clientName);
            $newAccessToken = $client->refreshAccessToken($refreshToken);

            $accessToken = $newAccessToken->getToken();

            $this->tokenSession->saveToken(
                $newAccessToken->getToken(),
                $newAccessToken->getExpires(),
                $newAccessToken->getRefreshToken() ?? $refreshToken,
            );
        }

        return $accessToken;
    }
}
