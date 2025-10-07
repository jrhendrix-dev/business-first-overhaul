<?php
// src/Mapper/Request/UserCreateRequestMapper.php
declare(strict_types=1);

namespace App\Mapper\Request;

use App\Dto\User\CreateUserDto;
use App\Mapper\Contracts\RequestMapperInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Maps POST JSON into CreateUserDto.
 */
final class UserCreateRequestMapper implements RequestMapperInterface
{
    public function fromRequest(Request $request): object
    {
        $data = (array) json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);

        return new CreateUserDto(
            firstName: (string) ($data['firstName'] ?? ''),
            lastName:  (string) ($data['lastName']  ?? ''),
            email:     (string) ($data['email']     ?? ''),
            userName:  (string) ($data['userName']  ?? ''),
            password:  (string) ($data['password']  ?? ''),
            role:      (string) ($data['role']      ?? 'ROLE_STUDENT'),
        );
    }
}
