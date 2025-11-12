<?php
declare(strict_types=1);

namespace App\Service\Payments;

use App\Entity\Payment\Order;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

/**
 * Small adapter around Stripe Checkout.
 * In Test Mode, uses sk_test... and never charges real money.
 *
 * @phpstan-type CreateOut array{url:string, sessionId:string, paymentIntentId:?string}
 */
final class StripeCheckoutProvider
{
    public function __construct(private readonly StripeClient $stripe) {}

    /**
     * Create a Checkout Session for the given Order.
     *
     * @param Order  $order
     * @param string $productName Human-readable name shown on Stripe
     * @param string $successUrl
     * @param string $cancelUrl
     * @return array{url:string, sessionId:string, paymentIntentId:?string}
     */
    public function createCheckout(Order $order, string $productName, string $successUrl, string $cancelUrl): array
    {
        try {
            $session = $this->stripe->checkout->sessions->create([
                'mode'        => 'payment',
                'success_url' => $successUrl.'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => $cancelUrl,
                'customer_email' => $order->getStudent()->getEmail(),
                'line_items'  => [[
                    'price_data' => [
                        'currency'    => strtolower($order->getCurrency()),
                        'unit_amount' => $order->getAmountTotalCents(),
                        'product_data' => [
                            'name'        => $productName,              // <-- here
                            'description' => 'Enrollment for '.$productName,
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'metadata' => [
                    'order_id'      => (string)$order->getId(),
                    'classroom_id'  => (string)$order->getClassroomId(),
                    'classroom_name'=> $productName,
                    'student_id'    => (string)$order->getStudent()->getId(),
                ],
            ], [
                'idempotency_key' => 'order_'.$order->getId().'_create_checkout',
            ]);

            return [
                'url'             => $session->url,
                'sessionId'       => $session->id,
                'paymentIntentId' => $session->payment_intent ?? null,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new \DomainException('Stripe error: '.$e->getMessage(), previous: $e);
        }
    }

    /**
     * Retrieve a Checkout Session by id.
     * @return \Stripe\Checkout\Session
     */
    public function retrieveSession(string $sessionId): \Stripe\Checkout\Session
    {
        try {
            return $this->stripe->checkout->sessions->retrieve($sessionId, []);
        } catch (ApiErrorException $e) {
            throw new \DomainException('Stripe error: '.$e->getMessage(), previous: $e);
        }
    }
}
