<?php

namespace App\Entity;

use App\Enum\UserRoleEnum;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
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
    private ?int $id = null;

    #[ORM\Column(length: 45, unique: true)]
    #[Assert\NotBlank]
    private string $userName;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $firstName;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $lastName;

    #[ORM\Column(length: 45, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $password;

    #[ORM\Column(name: 'create_time', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'string', length: 32, enumType: UserRoleEnum::class)]
    private UserRoleEnum $role;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\OneToMany(
        targetEntity: Enrollment::class,
        mappedBy: 'student',
        cascade: ['remove'],
        orphanRemoval: false
    )]
    private Collection $enrollments;

    public function __construct()
    {
        $this->createdAt   = new \DateTimeImmutable();
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

    /** @return int|null */
    public function getId(): ?int
    {
        return $this->id;
    }

    /** @return string */
    public function getUserName(): string
    {
        return $this->userName;
    }

    /** @return self */
    public function setUserName(string $userName): self
    {
        $this->userName = $userName;
        return $this;
    }

    /** @return string */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /** @return self */
    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    /** @return string */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /** @return self */
    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    /** @return string */
    public function getFullname(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    /** @return string */
    public function getEmail(): string
    {
        return $this->email;
    }

    /** @return self */
    public function setEmail(string $email): self
    {
        $this->email = strtolower(trim($email));
        return $this;
    }

    /** @return string */
    public function getPassword(): string
    {
        return $this->password;
    }

    /** @return self */
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    /** @return \DateTimeImmutable */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return self */
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /** @return bool */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /** @return self */
    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    /** {@inheritdoc} */
    public function getUserIdentifier(): string
    {
        // Keep consistent with your auth: using email (as before).
        return $this->email;
    }

    /**
     * {@inheritdoc}
     * @return string[]
     */
    public function getRoles(): array
    {
        if (!$this->isActive) {
            return [];
        }

        $roles = ['ROLE_USER']; // baseline for every authenticated user

        $roles[] = $this->role->value;

        return array_values(array_unique($roles));
    }

    /** @return UserRoleEnum */
    public function getRole(): UserRoleEnum
    {
        return $this->role;
    }

    /** @return self */
    public function setRole(UserRoleEnum $role): self
    {
        $this->role = $role;
        return $this;
    }

    /** @return bool */
    public function isStudent(): bool
    {
        return $this->role === UserRoleEnum::STUDENT;
    }

    /** @return bool */
    public function isTeacher(): bool
    {
        return $this->role === UserRoleEnum::TEACHER;
    }

    /** @return bool */
    public function isAdmin(): bool
    {
        return $this->role === UserRoleEnum::ADMIN;
    }

    /** {@inheritdoc} */
    public function eraseCredentials(): void
    {
        // No sensitive temporary data stored
    }
}
