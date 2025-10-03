<?php
// src/Service/AccountTokenService.php
declare(strict_types=1);

namespace App\Service;

use App\Entity\AccountToken;
use App\Entity\User;
use App\Enum\AccountTokenType;
use App\Repository\AccountTokenRepository;
use Doctrine\ORM\EntityManagerInterface;

final class AccountTokenService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AccountTokenRepository $tokens,
    ) {}

    /** @return array{raw:string, entity:AccountToken} */
    public function mint(User $user, AccountTokenType $type, \DateTimeImmutable $expiresAt, ?array $payload = null): array
    {
        $raw  = \bin2hex(\random_bytes(32));
        $hash = \hash('sha256', $raw);

        // Optional: nuke old tokens of same type
        $this->tokens->invalidateAllForUser($user->getId(), $type);

        $entity = new AccountToken($user, $type, $hash, $expiresAt, $payload);
        $this->em->persist($entity);
        $this->em->flush();

        return ['raw' => $raw, 'entity' => $entity];
    }

    public function consume(string $raw, AccountTokenType $type): AccountToken
    {
        $hash  = \hash('sha256', $raw);
        $token = $this->tokens->findValidByHash($hash, $type);

        if (!$token) {
            throw new \DomainException('invalid_or_expired_token');
        }
        $token->markUsed();
        $this->em->flush();

        return $token;
    }
}
