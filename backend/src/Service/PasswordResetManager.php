<?php

namespace App\Service;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Service\Contracts\PasswordResetTokenPort;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Handles creation, validation and consumption of password reset tokens.
 */
final class PasswordResetManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PasswordResetTokenPort $tokens,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly int $ttlSeconds = 3600,
    ) {}

    /**
     * Issue a new token (single-use). Returns the **plain** token to email.
     *
     * @param User $user
     * @param string|null $requestIp
     * @return string Plain token
     * @throws RandomException
     * @throws \DateMalformedStringException
     */
    public function issue(User $user, ?string $requestIp = null): string
    {
        // Optional hardening: expire other tokens for this user
        $this->tokens->invalidateAllForUser($user);

        $plain = bin2hex(random_bytes(32)); // 64 chars
        $digest = hash('sha256', $plain);

        $now = new \DateTimeImmutable();
        $token = new PasswordResetToken();
        $token->setUser($user);
        $token->setTokenHash($digest);
        $token->setCreatedAt($now);
        $token->setExpiresAt($now->modify("+{$this->ttlSeconds} seconds"));
        $token->setRequestIp($requestIp);

        $this->em->persist($token);
        $this->em->flush();

        return $plain;
    }

    /**
     * Consume a token and set a new password if valid.
     *
     * @param User   $user
     * @param string $plainToken
     * @param string $newPassword
     */
    public function consume(User $user, string $plainToken, string $newPassword): void
    {
        $hash = hash('sha256', $plainToken);

        $row = $this->tokens->findUsable($user, $hash);
        if (!$row) {
            throw new \RuntimeException('Invalid or expired token.');
        }

        $user->setPassword($this->hasher->hashPassword($user, $newPassword));
        $row->setUsedAt(new \DateTime());

        $this->em->flush();
    }
}
