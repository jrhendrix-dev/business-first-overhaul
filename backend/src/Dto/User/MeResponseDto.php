<?php
declare(strict_types=1);

namespace App\Dto\User;

/**
 * Read model for GET /api/me
 *
 * @phpstan-type UserMeArray array{
 *   id:int,
 *   email:string,
 *   roles: list<string>,
 *   firstName: string|null,
 *   lastName: string|null,
 *   fullName: string,
 *   role: string|null,
 *   twoFactorEnabled: bool
 * }
 */
final class MeResponseDto
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly array $roles,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $primaryRole = null, // serialized as "role" for BC
        public readonly bool $twoFactorEnabled = false,
    ) {}

    /**
     * @return UserMeArray
     */
    public function toArray(): array
    {
        $full = trim(implode(' ', array_filter([$this->firstName, $this->lastName])));
        return [
            'id'                => $this->id,
            'email'             => $this->email,
            'roles'             => array_values($this->roles),
            'firstName'         => $this->firstName,
            'lastName'          => $this->lastName,
            'fullName'          => $full,
            // BC: keep "role" for callers still using single-role
            'role'              => $this->primaryRole,
            'twoFactorEnabled'  => $this->twoFactorEnabled,
        ];
    }
}
