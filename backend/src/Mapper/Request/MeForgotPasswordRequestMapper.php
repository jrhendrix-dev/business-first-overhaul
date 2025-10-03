<?php
// src/Mapper/Request/MeForgotPasswordRequestMapper.php
declare(strict_types=1);

namespace App\Mapper\Request;

use App\Dto\User\Password\ForgotPasswordDto;
use Symfony\Component\HttpFoundation\Request;

/**
 * Maps HTTP request body to ForgotPasswordDto.
 */
final class MeForgotPasswordRequestMapper
{
    public function fromRequest(Request $request): ForgotPasswordDto
    {
        $data = \json_decode($request->getContent() ?: '{}', true, 512, \JSON_THROW_ON_ERROR);
        return ForgotPasswordDto::fromArray($data);
    }
}
