<?php

namespace App\Graph;

use Symfony\Component\HttpFoundation\RequestStack;

readonly class GraphTokenSession
{
    /**
     * @param RequestStack $requestStack
     */
    public function __construct(
        private RequestStack $requestStack,
    ) {}

    /**
     * Zwraca access token zapisany w sesji użytkownika.
     *
     * @return string Aktualny access token
     *
     * @throws \RuntimeException Gdy brak aktywnej sesji lub tokenu w sesji
     */
    public function getAccessToken(): ?string
    {
        $session = $this->requestStack->getSession();
        if (!$session) {
            throw new \RuntimeException('Brak aktywnej sesji');
        }

        $token = $session->get(GraphEnum::ACCESS_TOKEN->value);
        if (!is_string($token) || $token === '') {
            throw new \RuntimeException('Brak access tokena w sesji');
        }

        return $token;
    }

    /**
     * @param string $accessToken
     * @param int|null $expires
     * @param string|null $refreshToken
     * @return void
     */
    public function saveToken(string $accessToken, ?int $expires = null, ?string $refreshToken = null): void
    {
        $session = $this->requestStack->getSession();
        $session->set(GraphEnum::ACCESS_TOKEN->value, $accessToken);
        if ($expires !== null) {
            $session->set(GraphEnum::ACCESS_TOKEN_EXPIRES->value, $expires);
        }
        if ($refreshToken !== null) {
            $session->set(GraphEnum::REFRESH_TOKEN->value, $refreshToken);
        }
    }

    /**
     * @return int|null
     */
    public function getExpiresAt(): ?int
    {
        return $this->requestStack->getSession()?->get(GraphEnum::ACCESS_TOKEN_EXPIRES->value);
    }

    /**
     * @return string|null
     */
    public function getRefreshToken(): ?string
    {
        return $this->requestStack->getSession()?->get(GraphEnum::REFRESH_TOKEN->value);
    }

    /**
     * @return void
     */
    public function clear(): void
    {
        $session = $this->requestStack->getSession();

        if (!$session) {
            return;
        }
        $session->remove(GraphEnum::ACCESS_TOKEN->value);
        $session->remove(GraphEnum::ACCESS_TOKEN_EXPIRES->value);
        $session->remove(GraphEnum::REFRESH_TOKEN->value);
    }
}
