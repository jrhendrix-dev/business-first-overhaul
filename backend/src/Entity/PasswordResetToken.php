<?php

namespace App\Entity;

use App\Repository\PasswordResetTokenRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Single-use, short-lived password reset token.
 */
#[ORM\Entity(repositoryClass: PasswordResetTokenRepository::class)]
#[ORM\Table(name: 'password_reset_tokens')]
#[ORM\Index(columns: ['user_id'])]
#[ORM\Index(columns: ['expires_at'])]
class PasswordResetToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /** Token digest (hash). Never store the plain token. */
    #[ORM\Column(type: 'string', length: 255)]
    private string $tokenHash;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /** When the token was created. */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /** When the token expires. */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    /** When the token was consumed (nullable). */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $usedAt = null;

    /** Originating IP / UA (optional auditing) */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $requestIp = null;

    public function getId(): ?int { return $this->id; }

    public function getTokenHash(): string { return $this->tokenHash; }
    public function setTokenHash(string $tokenHash): void { $this->tokenHash = $tokenHash; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $user): void { $this->user = $user; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): void { $this->createdAt = $createdAt; }

    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function setExpiresAt(\DateTimeImmutable $expiresAt): void { $this->expiresAt = $expiresAt; }

    public function getUsedAt(): ?\DateTime { return $this->usedAt; }
    public function setUsedAt(?\DateTime $usedAt): void { $this->usedAt = $usedAt; }

    public function getRequestIp(): ?string { return $this->requestIp; }
    public function setRequestIp(?string $requestIp): void { $this->requestIp = $requestIp; }

    /** Check if token is already consumed or expired. */
    public function isUsable(\DateTimeImmutable $now = new \DateTimeImmutable()): bool
    {
        return $this->usedAt === null && $this->expiresAt > $now;
    }
}
