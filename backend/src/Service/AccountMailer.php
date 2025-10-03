<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class AccountMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromAddress,
        private readonly RateLimiterFactory $mailtrapLimiter, // injected factory
    ) {}

    /** Block until we can send the next email (1 msg/sec). */
    private function throttle(): void
    {
        // one global bucket for all SMTP sends (worker + http if any)
        $this->mailtrapLimiter
            ->create('smtp-global')
            ->reserve(1)
            ->wait(); // blocks just enough to respect 1 email/sec
    }

    public function sendEmailChangeConfirmationTo(string $targetEmail, string $confirmUrl, ?User $user = null): void
    {
        $this->throttle();

        $name = htmlspecialchars($user?->getFirstName() ?: 'there', \ENT_QUOTES);
        $email = (new Email())
            ->from($this->fromAddress)
            ->to($targetEmail)
            ->subject('Confirm your new email')
            ->html(sprintf(
                '<p>Hello %1$s,</p><p>Click to confirm your new email: <a href="%2$s">click this link</a></p>',
                $name,
                $confirmUrl
            ));

        $this->mailer->send($email);
    }

    public function notifyEmailChangeRequested(User $user, string $previousEmail): void
    {
        $this->throttle();

        $email = (new Email())
            ->from($this->fromAddress)
            ->to($previousEmail)
            ->subject('Email change requested')
            ->html('<p>If this wasn’t you, contact support.</p>');

        $this->mailer->send($email);
    }

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

    public function notifyPasswordChanged(User $user): void
    {
        $this->throttle();

        $email = (new Email())
            ->from($this->fromAddress)
            ->to($user->getEmail())
            ->subject('Your password was changed')
            ->text('If you did not perform this action, contact support immediately.');

        $this->mailer->send($email);
    }
}
