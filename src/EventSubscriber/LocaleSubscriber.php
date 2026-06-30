<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class LocaleSubscriber implements EventSubscriberInterface
{
    /**
     * @param string $defaultLocale
     */
    public function __construct(private string $defaultLocale = 'pl') {}

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 20]];
    }

    /**
     * Obsługuje ustawianie lokalizacji (locale) dla głównego żądania HTTP.
     *
     * Jeśli w zapytaniu występuje parametr `_locale`, jego wartość jest zapisywana
     * w sesji. Następnie locale żądania ustawiane jest na podstawie wartości
     * zapisanej w sesji lub domyślnej lokalizacji aplikacji.
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

        $request = $event->getRequest();
        $session = $request->getSession();
        if (!$session) {
            return;
        }

        if ($reqLocale = $request->query->get('_locale')) {
            $session->set('_locale', $reqLocale);
        }

        $request->setLocale($session->get('_locale', $this->defaultLocale));
    }
}
