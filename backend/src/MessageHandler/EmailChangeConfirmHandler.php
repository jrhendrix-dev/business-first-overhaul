<?php
namespace App\MessageHandler;

use App\Message\EmailChangeConfirmMessage;
use App\Repository\UserRepository;
use App\Service\AccountMailer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[AsMessageHandler]
final class EmailChangeConfirmHandler
{
    public function __construct(
        private readonly AccountMailer $mailer,
        private readonly UserRepository $users,
    ) {}

    public function __invoke(EmailChangeConfirmMessage $msg): void
    {
        $user = $this->users->find($msg->userId);
        if (!$user) { return; } // user deleted meanwhile; just skip

        $this->mailer->sendEmailChangeConfirmationTo($msg->targetEmail, $msg->confirmUrl, $user);
    }
}
