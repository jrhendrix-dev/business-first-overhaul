<?php

namespace App\Enum;

enum UserRoleEnum: int
{
    case ADMIN = 0;
    case TEACHER = 1;
    case STUDENT = 2;
}
