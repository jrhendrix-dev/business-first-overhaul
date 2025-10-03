<?php
// src/Dto/User/UserCreateDto.php
declare(strict_types=1);

namespace App\Dto\User;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for creating a User (request payload).
 */
final class UserCreateDto
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public readonly string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 255)]
    public readonly string $password;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public readonly string $firstName;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public readonly string $lastName;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public readonly string $username;

    /** @var string[] Symfony role strings (ROLE_*) */
    #[Assert\All([
        new Assert\NotBlank(),
        new Assert\Length(max: 50),
    ])]
    public readonly array $roles;

    public function __construct(
        string $email,
        string $password,
        string $firstName,
        string $lastName,
        string $userName,
        array $roles = ['ROLE_USER'],
    ) {
        $this->email     = $email;
        $this->password  = $password;
        $this->firstName = $firstName;
        $this->lastName  = $lastName;
        $this->username  = $userName;
        $this->roles     = $roles;
    }
}
