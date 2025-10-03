<?php
// src/Dto/User/UserResponseDto.php
declare(strict_types=1);

namespace App\Dto\User;

final class UserResponseDto
{
    public function __construct(
        public int $id,
        public string $userName,
        public string $email,
        public string $firstName,
        public string $lastName,
        public ?string $role,
        public bool $isActive,
        public \DateTimeInterface $createdAt,
        public string $fullName,
    ) {}
}
