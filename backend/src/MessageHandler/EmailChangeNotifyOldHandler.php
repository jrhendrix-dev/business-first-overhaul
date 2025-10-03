<?php
namespace App\MessageHandler;

use App\Message\EmailChangeNotifyOldMessage;
use App\Repository\UserRepository;
use App\Service\AccountMailer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[AsMessageHandler]
final class EmailChangeNotifyOldHandler
{
    public function __construct(
        private readonly AccountMailer $mailer,
        private readonly UserRepository $users,
    ) {}

    public function __invoke(EmailChangeNotifyOldMessage $msg): void
    {
        $user = $this->users->find($msg->userId);
        if (!$user) { return; }

        $this->mailer->notifyEmailChangeRequested($user, $msg->previousEmail);
    }
}
