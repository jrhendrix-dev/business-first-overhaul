<?php
declare(strict_types=1);

namespace App\Dto\User;

/**
 * Read model for GET /api/me
 *
 * @phpstan-type UserMeArray array{id:int, email:string, role: string|null}
 */
final class MeResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly ?string $role,
    ) {}

    /** @return UserMeArray */
    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'email' => $this->email,
            'role'  => $this->role,
        ];
    }
}
