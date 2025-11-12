<?php
// src/Message/WelcomeEmailMessage.php
declare(strict_types=1);

namespace App\Message;

/**
 * Mensaje para enviar el email de bienvenida tras registro.
 */
final class WelcomeEmailMessage
{
    public function __construct(
        public readonly int $userId
    ) {}
}
