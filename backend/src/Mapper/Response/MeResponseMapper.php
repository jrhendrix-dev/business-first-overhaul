<?php
// src/Mapper/Response/UserMeResponseMapper.php
declare(strict_types=1);

namespace App\Mapper\Response;

use App\Dto\User\MeResponseDto;
use App\Entity\User;

final class MeResponseMapper
{
    public function toResponse(User $user): MeResponseDto
    {
        return new MeResponseDto(
            id: (int)$user->getId(),
            email: (string)$user->getEmail(),
            role: $user->getRole()?->value
        );
    }
}
