<?php

namespace App\Entity;

use App\Enum\UserRoleEnum;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 45, unique: true)]
    #[Assert\NotBlank]
    #[Groups(['user:read'])]
    private string $username;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['user:read'])]
    private string $firstname;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['user:read'])]
    private string $lastname;

    #[ORM\Column(length: 45, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['user:read'])]
    private string $email;


    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $password;

    #[ORM\Column(name: 'create_time', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'integer', enumType: UserRoleEnum::class)]
    private UserRoleEnum $role;


    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\OneToMany(
        targetEntity: Enrollment::class,
        mappedBy: 'student',
        cascade: ['remove'],   // optional, remove student's enrollments when deleting user
        orphanRemoval: false   // keep false if you don’t want deletes when detaching
    )]
    private Collection $enrollments;

    /**
     * User constructor.
     * Initializes the user with a creation timestamp.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->enrollments = new ArrayCollection();
    }

    /** @return Collection<int, Enrollment> */
    public function getEnrollments(): Collection
    {
        return $this->enrollments;
    }

    public function addEnrollment(Enrollment $enrollment): self
    {
        if (!$this->enrollments->contains($enrollment)) {
            $this->enrollments->add($enrollment);
            $enrollment->setStudent($this);
        }
        return $this;
    }

    public function removeEnrollment(Enrollment $enrollment): self
    {
        if ($this->enrollments->removeElement($enrollment) && $enrollment->getStudent() === $this) {
            $enrollment->setStudent(null);
        }
        return $this;
    }

    /**
     * Gets the user's unique identifier.
     *
     * @return int|null The user ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gets the user's username (used for display purposes).
     *
     * @return string The username
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Sets the user's username.
     *
     * @param string $username The new username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * Gets the user's first name.
     *
     * @return string The first name
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * Sets the user's first name.
     *
     * @param string $firstname The new first name
     */
    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    /**
     * Gets the user's last name.
     *
     * @return string The last name
     */
    public function getLastname(): string
    {
        return $this->lastname;
    }

    /**
     * Sets the user's last name.
     *
     * @param string $lastname The new last name
     */
    public function setLastname(string $lastname): void
    {
        $this->lastname = $lastname;
    }

    /**
     * Gets the user's full name (first + last).
     *
     * @return string The full name
     */
    public function getFullname(): string
    {
        return $this->firstname . " " . $this->lastname;
    }

    /**
     * Gets the user's email address (used for authentication).
     *
     * @return string The email address
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Sets the user's email address.
     *
     * @param string $email The new email address
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * Gets the user's hashed password.
     *
     * @return string The password hash
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Sets the user's password (should be a hash).
     *
     * @param string $password The password hash to store
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * Gets the timestamp when the user was created.
     *
     * @return \DateTimeImmutable The creation date/time
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Sets the user's creation timestamp (typically not modified manually).
     *
     * @param \DateTimeImmutable $createdAt The new creation timestamp
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Checks if the user account is active.
     *
     * @return bool True if the account is active, false otherwise
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Sets the user account's active status.
     *
     * @param bool $isActive The new active status
     */
    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    /**
     * {@inheritdoc}
     *
     * Returns the user's identifier for authentication (email in this case).
     */
    public function getUserIdentifier(): string
    {
        return $this->email; // or return $this->username if preferred
    }

    /**
     * {@inheritdoc}
     *
     * Returns the user's security roles based on their UserRoleEnum.
     * Returns empty array if account is inactive.
     *
     * @return string[] The user's roles (e.g., ['ROLE_STUDENT'])
     */
    public function getRoles(): array
    {
        if (!$this->isActive) {
            return [];
        }

        return match ($this->role) {
            UserRoleEnum::ADMIN => ['ROLE_ADMIN'],      // Enum: 0
            UserRoleEnum::TEACHER => ['ROLE_TEACHER'],  // Enum: 1
            UserRoleEnum::STUDENT => ['ROLE_STUDENT'],  // Enum: 2
        };
    }

    /**
     * Gets the user's role from the UserRoleEnum.
     *
     * @return UserRoleEnum The user's role
     */
    public function getRole(): UserRoleEnum
    {
        return $this->role;
    }

    /**
     * Sets the user's role.
     *
     * @param UserRoleEnum $role The new role to assign
     */
    public function setRole(UserRoleEnum $role): void
    {
        $this->role = $role;
    }

    /**
     * Checks if the user has the STUDENT role.
     *
     * @return bool True if the user is a student, false otherwise
     */
    public function isStudent(): bool
    {
        return $this->role === UserRoleEnum::STUDENT;
    }

    /**
     * Checks if the user has the TEACHER role.
     *
     * @return bool True if the user is a teacher, false otherwise
     */
    public function isTeacher(): bool
    {
        return $this->role === UserRoleEnum::TEACHER;
    }

    /**
     * Checks if the user has the ADMIN role.
     *
     * @return bool True if the user is an admin, false otherwise
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRoleEnum::ADMIN;
    }

    /**
     * {@inheritdoc}
     *
     * Clears sensitive data from the user (not used in this implementation).
     */
    public function eraseCredentials(): void
    {
        // No sensitive temporary data stored
    }
}
