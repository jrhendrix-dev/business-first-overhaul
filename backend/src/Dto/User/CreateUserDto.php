<?php
// src/Dto/User/CreateUserDto.php
declare(strict_types=1);

namespace App\Dto\User;

use Symfony\Component\Validator\Constraints as Assert;
use App\Enum\UserRoleEnum;

/**
 * Request DTO for creating a user.
 */
final class CreateUserDto
{
    #[Assert\NotBlank] #[Assert\Length(max: 255)]
    public readonly string $firstName;

    #[Assert\NotBlank] #[Assert\Length(max: 255)]
    public readonly string $lastName;

    #[Assert\NotBlank] #[Assert\Email] #[Assert\Length(max: 255)]
    public readonly string $email;

    #[Assert\NotBlank] #[Assert\Length(max: 45)]
    public readonly string $userName;

    #[Assert\NotBlank]
    #[Assert\Length(min: 12, max: 255, minMessage: 'Password must be at least {{ limit }} characters.')]
    #[Assert\Regex(pattern: '/[A-Z]/', message: 'Password must contain at least one uppercase letter.')]
    #[Assert\Regex(pattern: '/[a-z]/', message: 'Password must contain at least one lowercase letter.')]
    #[Assert\Regex(pattern: '/\d/',   message: 'Password must contain at least one number.')]
    #[Assert\Regex(pattern: '/[^A-Za-z0-9]/', message: 'Password must contain at least one special character.')]
    #[Assert\NotCompromisedPassword(message: 'This password appears in data breaches; please choose another.')]
    public readonly string $password;

    /**
     * String role value (ROLE_*). We keep string to decouple HTTP from enum.
     * Use UserRoleEnum::from() at the service boundary.
     */
    #[Assert\NotBlank] #[Assert\Choice(callback: [UserRoleEnum::class, 'values'])]
    public readonly string $role;

    public function __construct(
        string $firstName,
        string $lastName,
        string $email,
        string $userName,
        string $password,
        string $role = 'ROLE_STUDENT',
    ) {
        $this->firstName = $firstName;
        $this->lastName  = $lastName;
        $this->email     = $email;
        $this->userName  = $userName;
        $this->password  = $password;
        $this->role      = $role;
    }
}
