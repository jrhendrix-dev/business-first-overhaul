<?php

namespace App\Enum;

enum UserRoleEnum: int
{
    case ADMIN = 1;
    case TEACHER = 2;
    case STUDENT = 3;

    public static function values(): array {
        return array_column(self::cases(), 'value');
    }

}
