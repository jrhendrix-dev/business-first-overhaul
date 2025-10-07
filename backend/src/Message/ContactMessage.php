<?php
declare(strict_types=1);

namespace App\Message;

final class ContactMessage
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $name,
        public readonly string  $email,
        public readonly string  $subject,
        public readonly string  $message,        // <-- note: body (not message)
        public readonly bool    $consent,
        public readonly ?string $ip = null,
        public readonly ?string $userAgent = null,
    ) {}

    public function getId(): string         { return $this->id; }
    public function getName(): string       { return $this->name; }
    public function getEmail(): string      { return $this->email; }
    public function getSubject(): string    { return $this->subject; }
    public function getMessage(): string    { return $this->message; } // alias
    public function getConsent(): bool      { return $this->consent; }
    public function getIp(): ?string        { return $this->ip; }
    public function getUserAgent(): ?string { return $this->userAgent; }
}
