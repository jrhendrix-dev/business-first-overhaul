<?php

namespace App\Entity;

use AllowDynamicProperties;
use App\Enum\UserRoleEnum;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[AllowDynamicProperties]
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
    private UserRoleEnum $role = UserRoleEnum::STUDENT;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\OneToMany(
        targetEntity: Enrollment::class,
        mappedBy: 'student',
        cascade: ['remove'],
        orphanRemoval: false
    )]
    private Collection $enrollments;

    // 2FA FIELDS
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $twoFactorEnabled = false;

    /**
     * Base32 secret for TOTP (encrypt at rest if using secrets).
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $totpSecret = null;

    /**
     * Hashed recovery codes (array of strings) — store hashed, never plaintext.
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $twoFactorRecoveryCodes = null;

    /**
     * “Remember device” optional — epoch seconds until which 2FA is not required on this device.
     *  manage this with a signed cookie token instead. Keeping here for completeness.
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $last2FAVerifiedAt = null;

    // Google Auth
    /**
     * Google subject (the stable Google user id 'sub'). Null if not linked.
     */
    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $googleSub = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $googleLinkedAt = null;

    /** Optional provider marker if you like */
    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $oauthProvider = null;

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

    // 2FA functions
    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorEnabled;
    }

    public function enableTwoFactor(): void
    {
        $this->twoFactorEnabled = true;
    }

    public function disableTwoFactor(): void
    {
        $this->twoFactorEnabled = false;
        $this->totpSecret = null;
        $this->twoFactorRecoveryCodes = null;
        $this->last2faVerifiedAt = null;
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function setTotpSecret(?string $secret): void
    {
        $this->totpSecret = $secret;
    }

    /** @return string[] */
    public function getTwoFactorRecoveryCodes(): array
    {
        return $this->twoFactorRecoveryCodes ?? [];
    }

    /** @param string[] $codes */
    public function setTwoFactorRecoveryCodes(array $codes): void
    {
        $this->twoFactorRecoveryCodes = $codes;
    }

    public function getLast2faVerifiedAt(): ?\DateTimeInterface
    {
        return $this->last2faVerifiedAt;
    }

    public function setLast2faVerifiedAt(?\DateTimeInterface $at): void
    {
        $this->last2faVerifiedAt = $at;
    }

    // Google Auth
    public function getGoogleSub(): ?string { return $this->googleSub; }
    public function setGoogleSub(?string $sub): self { $this->googleSub = $sub; return $this; }

    /** @return ?\DateTimeImmutable */
    public function getGoogleLinkedAt(): ?\DateTimeImmutable { return $this->googleLinkedAt; }

    /** @return $this */
    public function setGoogleLinkedAt(?\DateTimeImmutable $at): self { $this->googleLinkedAt = $at; return $this; }

    public function getOauthProvider(): ?string { return $this->oauthProvider; }
    public function setOauthProvider(?string $p): self { $this->oauthProvider = $p; return $this; }

}
