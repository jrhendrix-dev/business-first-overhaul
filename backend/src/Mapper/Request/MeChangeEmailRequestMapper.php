<?php
// src/Mapper/Request/MeChangeEmailRequestMapper.php
declare(strict_types=1);

namespace App\Mapper\Request;

use App\Dto\Me\ChangeEmailDto;
use Symfony\Component\HttpFoundation\Request;

final class MeChangeEmailRequestMapper
{
    public function fromRequest(Request $request): ChangeEmailDto
    {
        $data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        return new ChangeEmailDto(
            email:    (string)($data['email'] ?? ''),
            password: (string)($data['password'] ?? ''),
        );
    }
}
