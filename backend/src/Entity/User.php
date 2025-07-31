<?php

namespace App\Entity;

//use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "users")]
#[UniqueEntity(fields: ["email"], message: "There is already an account with this email.")]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id", type: "integer")]
    private ?int $id = null;

    #[ORM\Column(length: 45)]
    #[Assert\NotBlank]
    private string $username;

    #[ORM\Column(length: 45, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $password;

    #[ORM\Column(name: "create_time", type: "datetime_immutable")]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: "integer")]
    private int $role;

    #[ORM\ManyToOne(targetEntity: Classroom::class, inversedBy: 'students')]
    #[ORM\JoinColumn(name: "class_id", referencedColumnName: "id", nullable: true)]   //referencedColumnName=Foreign Key
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

    public function setClassroom(?Classroom $classroom): void
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
            0 => ['ROLE_ADMIN'],
            1 => ['ROLE_TEACHER'],
            2 => ['ROLE_STUDENT'],
            default => throw new \LogicException("Invalid user role: {$this->role}"),
        };
    }

    public function eraseCredentials(): void
    {
        // No sensitive temporary data stored
    }
}
