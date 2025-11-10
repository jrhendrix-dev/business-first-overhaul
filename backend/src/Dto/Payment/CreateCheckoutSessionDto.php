<?php
declare(strict_types=1);

namespace App\Dto\Payment;

/** Input DTO for creating a checkout session. */
final class CreateCheckoutSessionDto
{
    public function __construct(public int $classroomId) {}
}
