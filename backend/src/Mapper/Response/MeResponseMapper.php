<?php
// src/Mapper/Response/MeResponseMapper.php
declare(strict_types=1);

namespace App\Mapper\Response;

use App\Dto\User\MeResponseDto;
use App\Entity\User;

/**
 * Maps the authenticated user to the /api/me read model.
 */
final class MeResponseMapper
{
    /** Priority order: first match wins */
    private const ROLE_PRIORITY = ['ROLE_ADMIN', 'ROLE_TEACHER', 'ROLE_STUDENT'];

    /**
     * Build the MeResponseDto using the new signature:
     *  - roles[]   (array for frontend .includes())
     *  - firstName / lastName (for full name in navbar)
     *  - primaryRole (kept for BC; serialized as "role" in DTO->toArray())
     */
    public function toResponse(User $u): MeResponseDto
    {
        return new MeResponseDto(
            id: (int) $u->getId(),
            email: $u->getEmail(),
            roles: $u->getRoles(),
            firstName: $u->getFirstName(),
            lastName: $u->getLastName(),
            primaryRole: $this->primaryRole($u),
        );
    }

    private function primaryRole(User $u): ?string
    {
        $have = $u->getRoles();
        foreach (self::ROLE_PRIORITY as $r) {
            if (\in_array($r, $have, true)) {
                return $r;
            }
        }
        return null;
    }
}
