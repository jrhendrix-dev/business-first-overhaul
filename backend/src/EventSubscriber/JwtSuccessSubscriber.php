<?php
// src/EventSubscriber/JwtSuccessSubscriber.php
declare(strict_types=1);

namespace App\EventSubscriber;

use DateTimeImmutable;
use DateTimeInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Shapes the login JSON response for the frontend contract:
 * { "accessToken": "...", "expiresAt": "ISO-8601", "refreshToken": "..."? }
 */
final class JwtSuccessSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'lexik_jwt_authentication.on_authentication_success' => 'onAuthSuccess',
        ];
    }

    /**
     * Maps Lexik's default { token, exp } into our { accessToken, expiresAt, refreshToken? }.
     *
     * @phpstan-param AuthenticationSuccessEvent $event
     */
    public function onAuthSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData(); // e.g. ['token' => '...', 'exp' => 1730000000]
        $expiresAt = isset($data['exp'])
            ? (new DateTimeImmutable())->setTimestamp((int)$data['exp'])->format(DateTimeInterface::ATOM)
            : null;

        $payload = [
            'accessToken' => $data['token'] ?? null,
            'expiresAt'   => $expiresAt,
            // If you use a refresh-token solution, add it here:
            // 'refreshToken' => $data['refresh_token'] ?? null,
        ];

        $event->setData($payload);
    }
}
