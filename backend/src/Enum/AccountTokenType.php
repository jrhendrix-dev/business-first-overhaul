<?php
// src/Enum/AccountTokenType.php
declare(strict_types=1);

namespace App\Enum;

enum AccountTokenType: string
{
    case EMAIL_CHANGE   = 'EMAIL_CHANGE';
    case PASSWORD_RESET = 'PASSWORD_RESET';
}
