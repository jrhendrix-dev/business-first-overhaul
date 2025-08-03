<?php

namespace App\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTCreatedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            JWTDecodedEvent::class => 'onJWTDecoded',
        ];
    }

    public function onJWTDecoded(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();

        if (!isset($payload['roles'])) {
            return;
        }

        $event->getToken()->setAttribute('roles', $payload['roles']);
    }
}
