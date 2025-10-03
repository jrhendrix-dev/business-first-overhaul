<?php
// src/Mapper/Request/MeChangePasswordRequestMapper.php
declare(strict_types=1);

namespace App\Mapper\Request;

use App\Dto\User\Password\ChangePasswordDto;
use Symfony\Component\HttpFoundation\Request;

final class MeChangePasswordRequestMapper
{
    public function fromRequest(Request $request): ChangePasswordDto
    {
        $data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        return new ChangePasswordDto(
            currentPassword: (string)($data['currentPassword'] ?? ''),
            newPassword:     (string)($data['newPassword'] ?? ''),
            confirmPassword: (string)($data['confirmPassword'] ?? ''),
        );
    }
}
