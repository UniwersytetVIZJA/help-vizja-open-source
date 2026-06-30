<?php

namespace App\EventSubscriber;

use App\Graph\GraphTokenSession;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

readonly class LogoutSubscriber implements EventSubscriberInterface
{
    /**
     * @param GraphTokenSession $tokenSession
     */
    public function __construct(
        private GraphTokenSession $tokenSession,
    ) {}

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    /**
     * @param LogoutEvent $event
     * @return void
     */
    public function onLogout(LogoutEvent $event): void
    {
        $this->tokenSession->clear();
    }
}
