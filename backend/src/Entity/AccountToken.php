<?php
// src/Entity/AccountToken.php
declare(strict_types=1);

namespace App\Entity;

use App\Enum\AccountTokenType;
use App\Repository\AccountTokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccountTokenRepository::class)]
#[ORM\Table(name: 'account_token')]
class AccountToken
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /** SHA-256 hash of the raw token we email */
    #[ORM\Column(type: 'string', length: 64)]
    private string $tokenHash;

    #[ORM\Column(type: 'string', enumType: AccountTokenType::class)]
    private AccountTokenType $type;

    /** Optional JSON payload (e.g., {"newEmail":"..."} for EMAIL_CHANGE) */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $payload = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $usedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, AccountTokenType $type, string $tokenHash, \DateTimeImmutable $expiresAt, ?array $payload = null)
    {
        $this->user      = $user;
        $this->type      = $type;
        $this->tokenHash = $tokenHash;
        $this->expiresAt = $expiresAt;
        $this->payload   = $payload;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getUser(): User { return $this->user; }
    public function getType(): AccountTokenType { return $this->type; }
    public function getPayload(): ?array { return $this->payload; }
    public function isExpired(): bool { return $this->expiresAt <= new \DateTimeImmutable(); }
    public function isUsed(): bool { return $this->usedAt !== null; }
    public function markUsed(): void { $this->usedAt = new \DateTimeImmutable(); }
}
