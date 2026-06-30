<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RequestStack;

final class MsAccessTokenProvider
{
    /**
     * @param RequestStack $requestStack
     */
    public function __construct(private RequestStack $requestStack) {}

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        $session = $this->requestStack->getSession();
        $token = $session?->get('ms_access_token');

        if (!$token) {
            throw new \RuntimeException('Brak tokenu Microsoft – zaloguj się.');
        }

        return $token;
    }

    /**
     * @return bool
     */
    public function hasToken(): bool
    {
        return (bool)$this->requestStack->getSession()?->get('ms_access_token');
    }
}
