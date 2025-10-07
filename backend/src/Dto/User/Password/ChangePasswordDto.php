<?php
// src/Dto/Me/ChangePasswordDto.php
declare(strict_types=1);

namespace App\Dto\User\Password;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordDto
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $currentPassword = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 12, max: 255)]
        #[Assert\Regex(pattern: '/[A-Z]/', message: 'Must contain at least one uppercase letter.')]
        #[Assert\Regex(pattern: '/[a-z]/', message: 'Must contain at least one lowercase letter.')]
        #[Assert\Regex(pattern: '/\d/',   message: 'Must contain at least one number.')]
        #[Assert\Regex(pattern: '/[^A-Za-z0-9]/', message: 'Must contain at least one special character.')]
        #[Assert\NotCompromisedPassword]
        public readonly string $newPassword = '',

        #[Assert\NotBlank]
        public readonly string $confirmPassword = '',
    ) {}
}
