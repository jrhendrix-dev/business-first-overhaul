<?php
declare(strict_types=1);

namespace App\Dto\Payment;

/** Output DTO with redirect URL to Stripe (Test Mode). */
final class CheckoutSessionResponseDto
{
    public function __construct(public string $checkoutUrl) {}
}
