<?php
declare(strict_types=1);

namespace App\Entity\Payment;

use App\Entity\User;
use App\Enum\PaymentStatus;
use App\Repository\Payment\OrderRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Demo payment Order for a single classroom purchase.
 * Server-authoritative price; provider/test-mode metadata kept for audit.
 */
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $student;

    /** Price in cents, must be determined server-side. */
    #[ORM\Column(type: 'integer')]
    private int $amountTotalCents;

    /** ISO 4217 currency (e.g., EUR). */
    #[ORM\Column(type: 'string', length: 3)]
    private string $currency = 'EUR';

    #[ORM\Column(type: 'string', length: 16, enumType: PaymentStatus::class)]
    private PaymentStatus $status = PaymentStatus::STATUS_PENDING;

    /** Payment provider name (e.g., "stripe"). */
    #[ORM\Column(type: 'string', length: 32)]
    private string $provider = 'stripe';

    /** Stripe Checkout Session ID (nullable until created). */
    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    private ?string $providerSessionId = null;

    /** Stripe PaymentIntent ID (nullable). */
    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    private ?string $providerPaymentIntentId = null;

    /** Classroom being purchased (demo keeps a simple FK integer). */
    #[ORM\Column(type: 'integer')]
    private int $classroomId;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /**
     * @param User   $student     Purchasing student
     * @param int    $classroomId Target classroom id
     * @param int    $amountCents Price in cents (server-authoritative)
     * @param string $currency    ISO 4217 currency (default EUR)
     */
    public function __construct(User $student, int $classroomId, int $amountCents, string $currency = 'EUR')
    {
        $this->student = $student;
        $this->classroomId = $classroomId;
        $this->amountTotalCents = $amountCents;
        $this->currency = $currency;
        $this->createdAt = new \DateTimeImmutable();
    }

    // --- Getters/Setters ---

    public function getId(): ?int { return $this->id; }

    public function getStudent(): User { return $this->student; }

    public function getAmountTotalCents(): int { return $this->amountTotalCents; }

    public function getCurrency(): string { return $this->currency; }

    public function getStatus(): PaymentStatus { return $this->status; }
    public function setStatus(PaymentStatus $status): void { $this->status = $status; }

    public function getProvider(): string { return $this->provider; }

    public function getProviderSessionId(): ?string { return $this->providerSessionId; }
    public function setProviderSessionId(?string $id): void { $this->providerSessionId = $id; }

    public function getProviderPaymentIntentId(): ?string { return $this->providerPaymentIntentId; }
    public function setProviderPaymentIntentId(?string $id): void { $this->providerPaymentIntentId = $id; }

    public function getClassroomId(): int { return $this->classroomId; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
