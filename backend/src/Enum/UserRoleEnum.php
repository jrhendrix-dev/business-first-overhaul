<?php

namespace App\Enum;

enum UserRoleEnum: String
{
    case ADMIN = "ROLE_ADMIN";
    case TEACHER = "ROLE_TEACHER";
    case STUDENT = "ROLE_STUDENT";

    public static function values(): array {
        return array_column(self::cases(), 'value');
    }

}
