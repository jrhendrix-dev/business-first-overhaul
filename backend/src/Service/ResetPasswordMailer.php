<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Sends reset password emails with a frontend link.
 */
final class ResetPasswordMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $appName = 'Business First',
        private readonly string $fromEmail = 'no-reply@businessfirstacademy.net',
        private readonly string $frontendResetUrl = 'https://businessfirstacademy.net/reset-password', // change for local
    ) {}

    /**
     * Send the reset email.
     *
     * @param User   $user
     * @param string $plainToken
     */
    public function send(User $user, string $plainToken): void
    {
        $url = sprintf('%s?token=%s&uid=%d', $this->frontendResetUrl, urlencode($plainToken), $user->getId());

        $email = (new TemplatedEmail())
            ->to($user->getEmail())
            ->from($this->fromEmail)
            ->subject(sprintf('[%s] Password reset request', $this->appName))
            ->htmlTemplate('emails/password_reset.html.twig')
            ->context([
                'user' => $user,
                'resetUrl' => $url,
                'appName' => $this->appName,
            ]);

        $this->mailer->send($email);
    }
}
