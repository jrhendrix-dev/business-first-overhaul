<?php

namespace App\DTO;

use App\Enum\UserRoleEnum;
use Symfony\Component\Validator\Constraints as Assert;

class CreateUserDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    public string $firstName;

    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    public string $lastName;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 45)]
    public string $username;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public string $password;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Type(type: 'integer')]
    public int $role; // Will be converted to UserRoleEnum in the manager
}
