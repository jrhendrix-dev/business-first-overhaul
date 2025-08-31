<?php
declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ContactMessage;
use App\Service\ContactMailer;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ContactMessageHandler
{
    public function __construct(private readonly ContactMailer $mailer) {}

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(ContactMessage $message): void
    {
        $payload = [
            'id'         => $message->getId(),
            'name'       => $message->getName(),
            'email'      => $message->getEmail(),
            'subject'    => $message->getSubject(),
            'message'    => $message->getMessage(),   // <-- use body here
            'consent'    => $message->getConsent(),
            'ip'         => $message->getIp(),
            'user_agent' => $message->getUserAgent(),
        ];

        $this->mailer->send($payload);
    }
}
