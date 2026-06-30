<?php

namespace App\Graph;

use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use Microsoft\Kiota\Abstractions\Authentication\AuthenticationProvider;
use Microsoft\Kiota\Abstractions\RequestInformation;

final class TokenAuthProvider implements AuthenticationProvider
{
    /**
     * @var callable
     */
    private $tokenResolver;

    /**
     * @param callable $tokenResolver
     */
    public function __construct(callable $tokenResolver)
    {
        $this->tokenResolver = $tokenResolver;
    }

    /**
     * Dodaje nagłówek Authorization do żądania HTTP.
     *
     * Metoda pobiera aktualny token dostępu za pomocą resolvera
     * i dołącza go do żądania jako nagłówek Bearer.
     *
     * @param RequestInformation $request Żądanie HTTP do uwierzytelnienia
     * @param array|null $additionalAuthenticationContext Dodatkowy kontekst (nieużywany)
     *
     * @return Promise<void> Zakończona obietnica po zmodyfikowaniu żądania
     */
    public function authenticateRequest(RequestInformation $request, ?array $additionalAuthenticationContext = null): Promise
    {
        $token = ($this->tokenResolver)();

        $request->addHeaders([
            'Authorization' => 'Bearer ' . $token,
        ]);

        return new FulfilledPromise(null);
    }
}
