<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Payment\Order;
use App\Enum\PaymentStatus;
use App\Repository\ClassroomRepository;
use App\Service\EnrollmentManager;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/payments/stripe/webhook')]
final class StripeWebhookController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EnrollmentManager $enrollments,
        private readonly ClassroomRepository $classrooms,
        private readonly string $stripeWebhookSecret
    ) {}

    #[Route('', name: 'stripe_webhook', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();
        $sig     = $request->headers->get('stripe-signature');

        try {
            $event = Webhook::constructEvent($payload, $sig ?? '', $this->stripeWebhookSecret);
        } catch (\Throwable) {
            return new Response('Invalid signature', 400);
        }

        if (\in_array($event->type, ['checkout.session.completed', 'payment_intent.succeeded'], true)) {
            $obj   = $event->data->object;
            $meta  = $obj->metadata ?? null;
            $orderId = $meta['order_id'] ?? null;
            $classId = isset($meta['classroom_id']) ? (int)$meta['classroom_id'] : null;


            if ($orderId && $classId) {
                /** @var Order|null $order */
                $order = $this->em->getRepository(Order::class)->find((int)$orderId);
                if ($order && $order->getStatus() !== PaymentStatus::STATUS_PAID) {
                    $order->setStatus(PaymentStatus::STATUS_PAID);
                    $this->em->flush();

                    // Enroll via your manager’s API
                    if ($classroom = $this->classrooms->find($classId)) {
                        $this->enrollments->enroll($order->getStudent(), $classroom);
                    }
                }
            }


        }



        return new Response('ok', 200);
    }
}
