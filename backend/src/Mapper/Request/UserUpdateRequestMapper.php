<?php
// src/Mapper/Request/UserUpdateRequestMapper.php
declare(strict_types=1);

namespace App\Mapper\Request;

use App\Dto\User\UpdateUserDto;
use App\Enum\UserRoleEnum;
use App\Http\Exception\ValidationException;
use App\Mapper\Contracts\RequestMapperInterface;
use Symfony\Component\HttpFoundation\Request;

final class UserUpdateRequestMapper implements RequestMapperInterface
{
    public function fromRequest(Request $request): object
    {
        $data = (array) json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);

        $dto = new UpdateUserDto();
        $dto->firstName = array_key_exists('firstName', $data) ? (string) $data['firstName'] : null;
        $dto->lastName  = array_key_exists('lastName',  $data) ? (string) $data['lastName']  : null;
        $dto->email     = array_key_exists('email',     $data) ? (string) $data['email']     : null;
        $dto->userName  = array_key_exists('userName',  $data) ? (string) $data['userName']  : null;
        $dto->password  = array_key_exists('password',  $data) ? (string) $data['password']  : null;

        if (array_key_exists('role', $data)) {
            // Validate presence type + map to enum, else raise 422
            if (!is_string($data['role']) || null === ($enum = UserRoleEnum::tryFrom($data['role']))) {
                throw new ValidationException(['role' => 'Role not valid']);
            }
            $dto->role = $enum;
        }

        return $dto;
    }
}
