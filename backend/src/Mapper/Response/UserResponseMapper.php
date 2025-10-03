<?php
// src/Mapper/Response/UserResponseMapper.php
declare(strict_types=1);

namespace App\Mapper\Response;

use App\Dto\User\MeResponseDto;
use App\Dto\User\UserResponseDto;
use App\Entity\User;
use App\Mapper\Contracts\ResponseMapperInterface;

final class UserResponseMapper implements ResponseMapperInterface
{
    /** Priority order: first match wins */
    private const ROLE_PRIORITY = ['ROLE_ADMIN', 'ROLE_TEACHER', 'ROLE_STUDENT'];

    public function toResponse(object $source): object
    {
        /** @var User $u */
        $u = $source;

        return new UserResponseDto(
            id: (int) $u->getId(),
            userName: $u->getUserName(),
            email: $u->getEmail(),
            firstName: $u->getFirstName(),
            lastName: $u->getLastName(),
            role: $this->primaryRole($u),
            isActive: $u->isActive(),
            createdAt: $u->getCreatedAt(),
            fullName: $u->getFullname(),
        );
    }

    /**
     * Specialized mapping for GET /api/me
     */
    public function toMeResponse(object $source): MeResponseDto
    {
        /** @var User $u */
        $u = $source;

        return new MeResponseDto(
            id: (int) $u->getId(),
            email: $u->getEmail(),
            role: $this->primaryRole($u), // <- single top role, same priority list
        );
    }


    private function primaryRole(User $u): ?string
    {
        $have = $u->getRoles();
        foreach (self::ROLE_PRIORITY as $r) {
            if (in_array($r, $have, true)) {
                return $r;
            }
        }
        // If user only has ROLE_USER (or something unexpected), return null or "ROLE_USER"
        return null; // or: return in_array('ROLE_USER', $have, true) ? 'ROLE_USER' : null;
    }
}
