<?php
declare(strict_types=1);

namespace App\Controller\Api\Student;

use App\Entity\Payment\Order;
use App\Enum\PaymentStatus;
use App\Repository\Payment\OrderRepository;            // <-- correct namespace
use App\Repository\ClassroomRepository;
use App\Service\EnrollmentManager;
use App\Service\PurchaseClassManager;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/student/payments', name: 'student_payments_')]
final class PaymentController extends AbstractController
{
    public function __construct(
        private readonly PurchaseClassManager   $purchase,
        private readonly ClassroomRepository    $classrooms,
        private readonly OrderRepository        $orders,        // <-- fixed type
        private readonly EnrollmentManager      $enrollments,
        private readonly EntityManagerInterface $em,
        private readonly StripeClient           $stripe,
    ) {}

    #[Route('/checkout-session', name: 'checkout_session', methods: ['POST'])]
    #[IsGranted('ROLE_STUDENT')]
    public function createCheckoutSession(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent() ?: '[]', true) ?: [];
        $classroomId = (int)($payload['classroomId'] ?? 0);
        if ($classroomId <= 0) {
            return $this->json([
                'error' => ['code' => 'VALIDATION_FAILED', 'details' => ['classroomId' => 'Required.']],
            ], 400);
        }

        $student = $this->getUser();
        if (!$student) {
            return $this->json(['error' => ['code' => 'UNAUTHORIZED']], 401);
        }

        $frontend  = $_ENV['FRONTEND_URL'] ?? 'http://localhost:4200';
        $successUrl = rtrim($frontend, '/') . '/payment/success';
        $cancelUrl  = rtrim($frontend, '/') . '/payment/cancel';

        try {
            $res = $this->purchase->createCheckoutSession(
                studentId:   (int)$student->getId(),
                classroomId: $classroomId,
                successUrl:  $successUrl,
                cancelUrl:   $cancelUrl,
            );
            return $this->json($res, 201); // { checkoutUrl }
        } catch (\DomainException $e) {
            return $this->json([
                'error' => ['code' => 'VALIDATION_FAILED', 'details' => ['classroomId' => $e->getMessage()]],
            ], 400);
        } catch (\Throwable) {
            return $this->json(['error' => ['code' => 'INTERNAL_ERROR','details' => ['message' => 'Please try again.']]], 500);
        }
    }

    #[Route('/confirm', name: 'confirm', methods: ['GET'])]
    #[IsGranted('ROLE_STUDENT')]
    public function confirm(Request $request): JsonResponse
    {
        $sessionId = (string)$request->query->get('session_id', '');
        if ($sessionId === '') {
            return $this->json([
                'error' => ['code' => 'VALIDATION_FAILED', 'details' => ['session_id' => 'Required']],
            ], 400);
        }

        /** @var Order|null $order */
        $order = $this->orders->findOneBy(['providerSessionId' => $sessionId]);
        if (!$order) {
            try {
                $session = $this->stripe->checkout->sessions->retrieve($sessionId, []);
                $meta = $session->metadata ?? null;
                if ($meta && isset($meta['order_id'])) {
                    $order = $this->orders->find((int)$meta['order_id']);
                }
            } catch (\Throwable) { /* ignore */ }
        }

        if (!$order) {
            return $this->json(['ok' => true, 'status' => 'not_paid']);
        }

        if ($order->getStatus() === PaymentStatus::STATUS_PAID) {
            return $this->json(['ok' => true, 'status' => 'already_paid']);
        }

        try {
            $session = $this->stripe->checkout->sessions->retrieve($sessionId, []);
            $isPaid  = ($session->payment_status ?? null) === 'paid';
            if (!$isPaid) {
                return $this->json(['ok' => true, 'status' => 'not_paid']);
            }

            $order->setStatus(PaymentStatus::STATUS_PAID);
            $this->em->flush();

            $classroom = $this->classrooms->find($order->getClassroomId());
            if ($classroom) {
                $this->enrollments->enroll(student: $order->getStudent(), classroom: $classroom);
            }

            return $this->json(['ok' => true, 'status' => 'paid']);
        } catch (\Throwable) {
            return $this->json(['ok' => true, 'status' => 'not_paid']);
        }
    }
}
