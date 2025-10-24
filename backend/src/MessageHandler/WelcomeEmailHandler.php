<?php
// src/MessageHandler/WelcomeEmailHandler.php
declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\WelcomeEmailMessage;
use App\Repository\UserRepository;
use App\Service\AccountMailer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler que envía el correo de bienvenida.
 */
#[AsMessageHandler]
final class WelcomeEmailHandler
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AccountMailer $mailer,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(WelcomeEmailMessage $message): void
    {
        $user = $this->users->find($message->userId);
        if (!$user) {
            $this->logger->warning('WelcomeEmail: user not found', ['userId' => $message->userId]);
            return;
        }

        $this->mailer->sendWelcomeEmail($user);
    }
}
