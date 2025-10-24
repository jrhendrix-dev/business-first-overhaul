<?php
declare(strict_types=1);

namespace App\Dto\User;

/**
 * Read model for GET /api/me
 *
 * - Exposes first/last name so the frontend can show the full name.
 * - Exposes roles[] to align with the Angular helper: roles?.includes('ROLE_ADMIN')
 * - Keeps "role" (primary role) for backward compatibility (deprecated).
 *
 * @phpstan-type UserMeArray array{
 *   id:int,
 *   email:string,
 *   roles: list<string>,
 *   firstName: string|null,
 *   lastName: string|null,
 *   fullName: string,
 *   role: string|null
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
        public readonly ?string $primaryRole = null, // deprecated output key "role"
    ) {}

    /**
     * @return UserMeArray
     */
    public function toArray(): array
    {
        $full = trim(implode(' ', array_filter([$this->firstName, $this->lastName])));
        return [
            'id'        => $this->id,
            'email'     => $this->email,
            'roles'     => array_values($this->roles),
            'firstName' => $this->firstName,
            'lastName'  => $this->lastName,
            'fullName'  => $full,
            // BC: keep "role" for callers still using single-role
            'role'      => $this->primaryRole,
        ];
    }
}
