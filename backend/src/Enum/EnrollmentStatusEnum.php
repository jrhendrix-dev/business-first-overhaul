<?php
namespace App\Enum;

enum EnrollmentStatusEnum: string
{
    case ACTIVE = 'ACTIVE';
    case DROPPED = 'DROPPED';
    case COMPLETED = 'COMPLETED';
}
