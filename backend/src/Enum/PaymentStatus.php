<?php

namespace App\Enum;

enum PaymentStatus: String
{
    case STATUS_PENDING = "PENDING";
    case STATUS_PAID = "PAID";
    case STATUS_FAILED = "FAILED";

    case STATUS_REFUNDED = "REFUNDED";

    public static function values(): array {
        return array_column(self::cases(), 'value');
    }

}
