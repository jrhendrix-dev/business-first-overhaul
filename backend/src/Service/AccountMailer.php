<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Bridge\Twig\Mime\TemplatedEmail; // kept for consistency

/**
 * Handles all account-related email notifications (email change, password reset, welcome, etc.).
 */
final class AccountMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromAddress,
        private readonly RateLimiterFactory $mailtrapLimiter, // injected limiter factory (limiter.mailtrap_per_second)
    ) {}

    /**
     * Global throttling to prevent exceeding Mailtrap / SMTP rate limits.
     * Blocks just enough time to respect 1 email per second.
     */
    private function throttle(): void
    {
        $this->mailtrapLimiter
            ->create('smtp-global')
            ->reserve(1)
            ->wait();
    }

    /**
     * Sends a welcome email to a newly registered user.
     *
     * @param User $user The newly created user
     */
    public function sendWelcomeEmail(User $user): void
    {
        $this->throttle();

        $name = htmlspecialchars($user->getFirstName() ?: $user->getUserName() ?: 'there', \ENT_QUOTES);
        $frontend = htmlspecialchars((string)($_ENV['FRONTEND_URL'] ?? 'http://localhost:4200'), \ENT_QUOTES);

        $email = (new Email())
            ->from($this->fromAddress)
            ->to($user->getEmail())
            ->subject('Welcome to Business First!')
            ->html(
                sprintf(
                    '<p>Hello %1$s,</p>
                     <p>Thank you for registering at <strong>Business First Language Center</strong>.</p>
                     <p>You can log in anytime using your credentials.</p>
                     <p style="margin-top:14px;">
                        <a href="%2$s/login">Log in now</a>
                     </p>
                     <p style="color:#666;font-size:12px;margin-top:18px;">
                        If you didn’t create this account, please ignore this email.
                     </p>',
                    $name,
                    $frontend
                )
            )
            ->text(
                sprintf(
                    "Hello %s,\n\n".
                    "Thank you for registering at Business First Language Center.\n\n".
                    "You can log in here: %s/login\n\n".
                    "If you didn’t create this account, please ignore this message.\n",
                    html_entity_decode($name, \ENT_QUOTES),
                    (string)($_ENV['FRONTEND_URL'] ?? 'http://localhost:4200')
                )
            );

        $this->mailer->send($email);
    }

    /**
     * Sends a confirmation email for an email change request.
     */
    public function sendEmailChangeConfirmationTo(string $targetEmail, string $confirmUrl, ?User $user = null): void
    {
        $this->throttle();

        $name = htmlspecialchars($user?->getFirstName() ?: 'there', \ENT_QUOTES);
        $email = (new Email())
            ->from($this->fromAddress)
            ->to($targetEmail)
            ->subject('Confirm your new email address')
            ->html(sprintf(
                '<p>Hello %1$s,</p><p>Click this link to confirm your new email: <a href="%2$s">confirm now</a></p>',
                $name,
                $confirmUrl
            ));

        $this->mailer->send($email);
    }

    /**
     * Notifies the user’s old email that a change was requested.
     */
    public function notifyEmailChangeRequested(User $user, string $previousEmail): void
    {
        $this->throttle();

        $email = (new Email())
            ->from($this->fromAddress)
            ->to($previousEmail)
            ->subject('Email change requested')
            ->html('<p>If this wasn’t you, please contact support immediately.</p>');

        $this->mailer->send($email);
    }

    /**
     * Sends a password reset link to the user.
     */
    public function sendPasswordResetLink(User $user, string $resetUrl): void
    {
        $this->throttle();

        $email = (new Email())
            ->from($this->fromAddress)
            ->to($user->getEmail())
            ->subject('Reset your password')
            ->text("Use this link to reset your password: $resetUrl");

        $this->mailer->send($email);
    }

    /**
     * Notifies the user that their password has been changed.
     */
    public function notifyPasswordChanged(User $user): void
    {
        $this->throttle();

        $email = (new Email())
            ->from($this->fromAddress)
            ->to($user->getEmail())
            ->subject('Your password has been changed')
            ->text('If you did not perform this action, contact support immediately.');

        $this->mailer->send($email);
    }
}
