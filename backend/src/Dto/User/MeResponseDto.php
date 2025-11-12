<?php
declare(strict_types=1);

namespace App\Dto\User;

/**
 * Read model for GET /api/me
 *
 * @phpstan-type UserMeArray array{
 *   id:int,
 *   email:string,
 *   roles:list<string>,
 *   firstName:string|null,
 *   lastName:string|null,
 *   fullName:string,
 *   role:string|null,
 *   twoFactorEnabled:bool,
 *   hasGoogleLink:bool,
 *   googleLinkedAt:string|null
 * }
 */
final class MeResponseDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        /** @var list<string> */
        public readonly array $roles,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $primaryRole = null, // serialized as "role"
        public readonly bool $twoFactorEnabled = false,
        public readonly bool $hasGoogleLink = false,
        public readonly ?\DateTimeImmutable $googleLinkedAt = null, // <-- NEW
    ) {}

    public function toArray(): array
    {
        $full = trim(implode(' ', array_filter([$this->firstName, $this->lastName])));
        return [
            'id'               => $this->id,
            'email'            => $this->email,
            'roles'            => array_values($this->roles),
            'firstName'        => $this->firstName,
            'lastName'         => $this->lastName,
            'fullName'         => $full,
            'role'             => $this->primaryRole,
            'twoFactorEnabled' => $this->twoFactorEnabled,
            'hasGoogleLink'    => $this->hasGoogleLink,
            'googleLinkedAt'   => $this->googleLinkedAt?->format(DATE_ATOM), // ISO 8601
        ];
    }
}

