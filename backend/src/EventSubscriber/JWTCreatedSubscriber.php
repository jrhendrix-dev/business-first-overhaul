<?php
// src/EventSubscriber/JWTCreatedSubscriber.php
namespace App\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds custom claims (e.g., roles) to the JWT at creation time.
 */
final class JWTCreatedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            JWTCreatedEvent::class => 'onJWTCreated',
        ];
    }

    /**
     * @param JWTCreatedEvent $event
     */
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $data = $event->getData();              // current payload
        $user = $event->getUser();

        // Ensure roles claim is present (adjust key name as your frontend expects)
        $data['roles'] = $user?->getRoles() ?? [];

        $event->setData($data);
    }
}
