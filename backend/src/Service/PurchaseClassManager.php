<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Payment\Order;
use App\Enum\PaymentStatus;
use App\Repository\ClassroomRepository;
use App\Repository\UserRepository;
use App\Service\Payments\StripeCheckoutProvider;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Orchestrates the demo payment flow (Stripe Test Mode).
 */
final class PurchaseClassManager
{
    public function __construct(
        private readonly ClassroomRepository   $classrooms,
        private readonly UserRepository        $users,
        private readonly StripeCheckoutProvider $stripe,
        private readonly EntityManagerInterface $em,
        private readonly EnrollmentManager      $enrollments,   // <-- NEW
    ) {}

    /**
     * Creates a server-side Checkout Session & persists Order.
     * @return array{checkoutUrl:string}
     */
    public function createCheckoutSession(
        int $studentId,
        int $classroomId,
        string $successUrl,
        string $cancelUrl
    ): array {
        $student = $this->users->find($studentId);
        if (!$student) {
            throw new \DomainException('Student not found.');
        }

        $classroom = $this->classrooms->find($classroomId);
        if (!$classroom) {
            throw new \DomainException('Classroom not found.');
        }

        $amountCents = (int) ($classroom->getPriceCents() ?? 0);
        if ($amountCents <= 0) {
            throw new \DomainException('Invalid price.');
        }

        $order = new Order($student, $classroom->getId(), $classroom->getPriceCents(), $classroom->getCurrency());
        $order->setStatus(PaymentStatus::STATUS_PENDING);

        $this->em->persist($order);
        $this->em->flush(); // need ID for Stripe metadata

        $created = $this->stripe->createCheckout(
            $order,
            "Class " . $classroom->getName(),
            $successUrl,
            $cancelUrl
        );

        $order->setProviderSessionId($created['sessionId']);
        if ($created['paymentIntentId']) {
            $order->setProviderPaymentIntentId($created['paymentIntentId']);
        }
        $this->em->flush();

        return ['checkoutUrl' => $created['url']];
    }

    /**
     * Confirm a Checkout Session with Stripe and enroll the student.
     * Idempotent: if already PAID, it just returns success again.
     *
     * @return array{ok:true, orderId:int}
     */
    public function confirmAndEnrollFromSession(string $sessionId): array
    {
        // 1) Verify with Stripe
        $session = $this->stripe->retrieveSession($sessionId);

        // Payment must be "paid" (Stripe sets both status/flags on success).
        $isPaid = ($session->payment_status === 'paid') || ($session->status === 'complete');
        if (!$isPaid) {
            throw new \DomainException('Session is not paid.');
        }

        // 2) Resolve our Order
        /** @var Order|null $order */
        $order = $this->em->getRepository(Order::class)->findOneBy(['providerSessionId' => $sessionId]);
        if (!$order) {
            // Fallback: try metadata->order_id (if present)
            $metaOrderId = $session->metadata['order_id'] ?? null;
            if ($metaOrderId) {
                $order = $this->em->find(Order::class, (int)$metaOrderId);
            }
        }
        if (!$order) {
            throw new \DomainException('Order not found for this session.');
        }

        // 3) Idempotency
        if ($order->getStatus() === PaymentStatus::STATUS_PAID) {
            return ['ok' => true, 'orderId' => $order->getId()];
        }

        // 4) Mark PAID + persist paymentIntent if available
        if (!empty($session->payment_intent) && !$order->getProviderPaymentIntentId()) {
            $order->setProviderPaymentIntentId((string)$session->payment_intent);
        }
        $order->setStatus(PaymentStatus::STATUS_PAID);

        // 5) Enroll student to the class (idempotent at EnrollmentManager)
        $classroom = $this->classrooms->find($order->getClassroomId());
        if (!$classroom) {
            throw new \DomainException('Classroom referenced by order not found.');
        }
        $this->enrollments->enroll($order->getStudent(), $classroom);

        $this->em->flush();

        return ['ok' => true, 'orderId' => $order->getId()];
    }
}
