<?php

namespace App\Entity;

use App\Enum\UserRoleEnum;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "users")]
#[UniqueEntity(fields: ["email"], message: "There is already an account with this email.")]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[Groups(['classroom:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id", type: "integer")]
    private ?int $id = null;

    #[Groups(['classroom:read'])]
    #[ORM\Column(length: 45, unique: true)]
    #[Assert\NotBlank]
    private string $username;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['classroom:read'])]
    private string $firstname;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['classroom:read'])]
    private string $lastname;

    #[ORM\Column(length: 45, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['classroom:read'])]
    private string $email;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $password;

    #[ORM\Column(name: "create_time", type: "datetime_immutable")]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: "integer", enumType: UserRoleEnum::class)]
    private UserRoleEnum $role;

    #[ORM\ManyToOne(targetEntity: Classroom::class, inversedBy: 'students')]
    #[ORM\JoinColumn(name: "class_id", referencedColumnName: "id", onDelete:'SET NULL')]   //referencedColumnName=Foreign Key
    private ?Classroom $classroom = null;

    #[ORM\Column(name: "is_active", type: "boolean", options: ["default" => true])]
    private bool $isActive = true;

    // Constructor
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and setters...

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * @param string $firstname
     */
    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    /**
     * @return string
     */
    public function getLastname(): string
    {
        return $this->lastname;
    }

    /**
     * @param string $lastname
     */
    public function setLastname(string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getClassroom(): ?Classroom
    {
        return $this->classroom;
    }

    public function setClassroom(?Classroom $classroom): void  //bidirectional association.
    {
        $this->classroom = $classroom;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    // Symfony UserInterface methods

    public function getUserIdentifier(): string
    {
        return $this->email; // or return $this->username if preferred
    }

    public function getRoles(): array
    {
        return match ($this->role) {
            UserRoleEnum::ADMIN => ['ROLE_ADMIN'],      // Enum: 0
            UserRoleEnum::TEACHER => ['ROLE_TEACHER'],  // Enum: 1
            UserRoleEnum::STUDENT => ['ROLE_STUDENT'],  // Enum: 2
        };
    }


    public function getRole(): UserRoleEnum
    {
        return $this->role;
    }

    public function setRole(UserRoleEnum $role): void
    {
        $this->role = $role;
    }


    public function eraseCredentials(): void
    {
        // No sensitive temporary data stored
    }
}
