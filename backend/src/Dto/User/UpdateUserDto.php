<?php
// src/Dto/User/UpdateUserDto.php
declare(strict_types=1);

namespace App\Dto\User;

use Symfony\Component\Validator\Constraints as Assert;
use App\Enum\UserRoleEnum;

/**
 * Request DTO for partial updates; only non-null fields are applied.
 */
final class UpdateUserDto
{
    #[Assert\Length(max: 255)]
    public ?string $firstName = null;

    #[Assert\Length(max: 255)]
    public ?string $lastName = null;

    #[Assert\Email]
    #[Assert\Length(max: 255)]
    public ?string $email = null;

    #[Assert\Length(max: 45)]
    public ?string $userName = null;

    #[Assert\Length(min: 12, max: 255, minMessage: 'Password must be at least {{ limit }} characters.')]
    #[Assert\Regex(pattern: '/[A-Z]/', message: 'Password must contain at least one uppercase letter.')]
    #[Assert\Regex(pattern: '/[a-z]/', message: 'Password must contain at least one lowercase letter.')]
    #[Assert\Regex(pattern: '/\d/',   message: 'Password must contain at least one number.')]
    #[Assert\Regex(pattern: '/[^A-Za-z0-9]/', message: 'Password must contain at least one special character.')]
    #[Assert\NotCompromisedPassword(message: 'This password appears in data breaches; please choose another.')]
    public ?string $password = null;

    /** Optional; when set it must be a valid enum. */
    public ?UserRoleEnum $role = null;
}
