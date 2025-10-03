<?php
// src/Dto/User/Password/ForgotPasswordDto.php
declare(strict_types=1);

namespace App\Dto\User\Password;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for initiating a password reset flow.
 *
 * Carries the email where the reset link should be sent.
 */
final class ForgotPasswordDto
{
    /**
     * Target email address to send the reset link to.
     * Validation is applied even though the action must be generic to
     * avoid user enumeration; the service must still NOT leak existence.
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public readonly string $email = '',
    ) {}

    /**
     * Create from array (camelCase or snake_case).
     *
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: (string)($data['email'] ?? ''),
        );
    }
}
