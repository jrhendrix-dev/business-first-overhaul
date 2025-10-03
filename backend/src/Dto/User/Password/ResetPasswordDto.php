<?php
// src/Dto/User/Password/ResetPasswordDto.php
declare(strict_types=1);

namespace App\Dto\User\Password;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for confirming a password reset with a token + new password.
 */
final class ResetPasswordDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 32, max: 128, minMessage: 'Invalid token.')]
        public readonly string $token = '',

        // Password policy (same used elsewhere)
        #[Assert\NotBlank]
        #[Assert\Length(min: 12, max: 255)]
        #[Assert\Regex(pattern: '/[A-Z]/', message: 'Password must contain at least one uppercase letter.')]
        #[Assert\Regex(pattern: '/[a-z]/', message: 'Password must contain at least one lowercase letter.')]
        #[Assert\Regex(pattern: '/\d/',   message: 'Password must contain at least one number.')]
        #[Assert\Regex(pattern: '/[^A-Za-z0-9]/', message: 'Password must contain at least one special character.')]
        #[Assert\NotCompromisedPassword]
        public readonly string $newPassword = '',
    ) {}

    /**
     * Create from array (camelCase or snake_case).
     *
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            token:       (string)($data['token'] ?? ''),
            newPassword: (string)($data['newPassword'] ?? $data['new_password'] ?? ''),
        );
    }
}
