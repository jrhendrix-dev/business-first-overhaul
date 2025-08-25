<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for partial user updates.
 * All fields are optional; only provided ones are validated.
 */
final class UpdateUserDTO
{
    /** @var string|null */
    #[Assert\Length(min: 1, max: 100)]
    public ?string $firstName = null;

    /** @var string|null */
    #[Assert\Length(min: 1, max: 100)]
    public ?string $lastName = null;

    /** @var string|null */
    #[Assert\Email]
    public ?string $email = null;

    /** @var string|null */
    #[Assert\Length(min: 3, max: 64)]
    public ?string $username = null;

    /** @var string|null New plain password (if provided, must be strong enough) */
    #[Assert\Length(min: 8, max: 255)]
    public ?string $password = null;

    /** @var int|null Backed enum value for UserRoleEnum, admin-only */
    public ?int $role = null;
}
