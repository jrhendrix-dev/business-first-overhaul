<?php
namespace App\Message;

final class EmailChangeConfirmMessage
{
    public function __construct(
        public readonly int $userId,
        public readonly string $targetEmail,
        public readonly string $confirmUrl,
    ) {}
}
