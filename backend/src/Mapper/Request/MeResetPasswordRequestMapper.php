<?php
// src/Mapper/Request/MeResetPasswordRequestMapper.php
declare(strict_types=1);

namespace App\Mapper\Request;

use App\Dto\User\Password\ResetPasswordDto;
use Symfony\Component\HttpFoundation\Request;

/**
 * Maps HTTP request body to ResetPasswordDto.
 */
final class MeResetPasswordRequestMapper
{
    public function fromRequest(Request $request): ResetPasswordDto
    {
        $data = \json_decode($request->getContent() ?: '{}', true, 512, \JSON_THROW_ON_ERROR);
        return ResetPasswordDto::fromArray($data);
    }
}
