<?php
namespace App\Message;

final class EmailChangeNotifyOldMessage
{
    public function __construct(
        public readonly int $userId,
        public readonly string $previousEmail,
    ) {}
}
