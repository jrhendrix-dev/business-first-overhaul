<?php
// src/Mapper/Request/UserRequestMapper.php
declare(strict_types=1);

namespace App\Mapper\Request;

use App\Dto\User\CreateUserDto; // <-- correct import
use App\Mapper\Contracts\RequestMapperInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Maps POST /users payload into CreateUserDto.
 */
final class UserRequestMapper implements RequestMapperInterface
{
    public function fromRequest(Request $request): object
    {
        $data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);

        return new CreateUserDto(
            firstName: (string) ($data['firstName'] ?? ''),
            lastName:  (string) ($data['lastName']  ?? ''),
            email:     (string) ($data['email']     ?? ''),
            userName:  (string) ($data['userName']  ?? ''), // <-- camel N
            password:  (string) ($data['password']  ?? ''),
            role:      (string) ($data['role']      ?? 'ROLE_STUDENT'), // <-- single role, not roles[]
        );
    }
}
