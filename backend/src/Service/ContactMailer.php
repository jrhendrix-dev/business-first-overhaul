<?php
declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

/**
 * Sends contact-form emails using Symfony Mailer.
 */
final class ContactMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $recipientEmail,   // env: CONTACT_RECIPIENT
        private readonly string $recipientName = 'Admissions'
    ) {}

    /**
     * @throws \InvalidArgumentException If required fields are invalid.
     */

    public function send(array $payload): void
    {
        // --- Validation guards (service-level hardening) ---
        $message = trim((string)($payload['message'] ?? ''));
        if ($message === '') {
            throw new \InvalidArgumentException('message must not be empty');
        }

        $name    = (string)($payload['name'] ?? '');
        $subject = (string)($payload['subject'] ?? '');

        // prevent header injection in displayable fields
        if (preg_match('/[\r\n]/', $name) || preg_match('/[\r\n]/', $subject)) {
            throw new \InvalidArgumentException('invalid header characters');
        }

        // build your TemplatedEmail as before, using $message (already trimmed)
        $email = (new TemplatedEmail())
            ->to(new Address($this->recipientEmail, $this->recipientName))
            ->replyTo(new Address($payload['email'], $name))
            ->subject('[Contact] ' . $subject)
            ->htmlTemplate('emails/contact.html.twig')
            ->context([
                'id'           => $payload['id'],
                'sender_name'  => $name,
                'sender_email' => $payload['email'],
                'subject'      => $subject,
                'message'      => $message,
                'consent'      => (bool) $payload['consent'],
                'ip'           => $payload['ip'] ?? null,
                'user_agent'   => $payload['user_agent'] ?? null,
            ]);

        $this->mailer->send($email);
    }

}
