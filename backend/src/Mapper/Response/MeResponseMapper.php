<?php
declare(strict_types=1);

namespace App\Mapper\Response;

use App\Dto\User\MeResponseDto;
use App\Entity\User;

final class MeResponseMapper
{
    private const ROLE_PRIORITY = ['ROLE_ADMIN', 'ROLE_TEACHER', 'ROLE_STUDENT'];

    public function toResponse(User $u): MeResponseDto
    {
        return new MeResponseDto(
            id: (int) $u->getId(),
            email: $u->getEmail(),
            roles: $u->getRoles(),
            firstName: $u->getFirstName(),
            lastName: $u->getLastName(),
            primaryRole: $this->primaryRole($u),
            twoFactorEnabled: $u->isTwoFactorEnabled(),
            hasGoogleLink: $u->getGoogleSub() !== null,
            googleLinkedAt: $u->getGoogleLinkedAt(),
        );
    }

    private function primaryRole(User $u): ?string
    {
        $have = $u->getRoles();
        foreach (self::ROLE_PRIORITY as $r) {
            if (\in_array($r, $have, true)) return $r;
        }
        return null;
    }
}
