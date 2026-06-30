<?php

namespace App\EventSubscriber;

use App\Database\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserBlockSubscriber implements EventSubscriberInterface
{
    /**
     * @var TokenStorageInterface
     */
    private TokenStorageInterface $tokenStorage;
    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param RouterInterface $router
     */
    public function __construct(TokenStorageInterface $tokenStorage, RouterInterface $router)
    {
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onKernelRequest',
        ];
    }

    /**
     * Wymusza wylogowanie nieaktywnych użytkowników.
     *
     * Listener sprawdza status zalogowanego użytkownika przy każdym
     * głównym żądaniu HTTP. Jeśli konto użytkownika jest nieaktywne,
     * następuje wyczyszczenie sesji i przekierowanie do wylogowania.
     *
     * @param RequestEvent $event Zdarzenie kernel.request
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isActive) {
            $this->tokenStorage->setToken(null);

            $request = $event->getRequest();
            $request->getSession()->invalidate();

            $url = $this->router->generate('admin_logout');
            $event->setResponse(new RedirectResponse($url));
        }
    }
}
