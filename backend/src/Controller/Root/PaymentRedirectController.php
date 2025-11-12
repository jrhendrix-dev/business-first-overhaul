<?php
declare(strict_types=1);

namespace App\Controller\Root;

use App\Service\Payments\StripeCheckoutProvider;
use App\Service\PurchaseClassManager;
use App\Service\Security\AuthService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class PaymentRedirectController
{
    public function __construct(
        private StripeCheckoutProvider $stripe,
        private PurchaseClassManager $purchases,
        private AuthService $auth,
    ) {}

    #[Route('/api/payment/verify', name: 'payment_redirect_verify', methods: ['GET'])]
    public function verify(Request $req): JsonResponse
    {
        $sessionId = (string) $req->query->get('session_id', '');
        if ($sessionId === '') {
            return new JsonResponse(['ok' => false, 'error' => 'missing_session_id'], 400);
        }

        try {
            $res = $this->purchases->confirmAndEnrollFromSession($sessionId);
            return new JsonResponse(['ok' => true, 'orderId' => $res['orderId']]);
        } catch (\Throwable) {
            return new JsonResponse(['ok' => false, 'error' => 'confirm_failed'], 409);
        }
    }

    // Prefer POST for starting a checkout (side-effect)
    #[Route('/api/payment/start', name: 'payment_redirect_start', methods: ['POST'])]
    public function start(Request $req): JsonResponse
    {
        $classroomId = (int) $req->query->get('classroom_id', 0);
        $studentId   = $this->auth->require()->getId();

        $successUrl = $req->getSchemeAndHttpHost().'/payment/success';
        $cancelUrl  = $req->getSchemeAndHttpHost().'/payment/cancel';

        $out = $this->purchases->createCheckoutSession(
            $studentId,
            $classroomId,
            $successUrl,
            $cancelUrl
        );

        return new JsonResponse($out);
    }
}
