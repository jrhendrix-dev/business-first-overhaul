<?php
declare(strict_types=1);

namespace App\Service\Adapters;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use App\Service\Contracts\PasswordResetTokenPort;

/**
 * Doctrine-backed adapter for the {@see PasswordResetTokenPort} contract.
 *
 * This class wraps the concrete {@see PasswordResetTokenRepository} so that
 * higher-level services (e.g. {@see \App\Service\PasswordResetManager})
 * depend only on the {@see PasswordResetTokenPort} abstraction.
 *
 * Benefits:
 * - Keeps business services testable (they mock the interface, not Doctrine internals).
 * - Allows alternative implementations (e.g. cache, external service) without
 *   changing the service code.
 * - Prevents mocking final Doctrine repositories, which PHPUnit cannot double.
 */
final class PasswordResetTokenDoctrinePort implements PasswordResetTokenPort
{
    /**
     * @param PasswordResetTokenRepository $repo Concrete Doctrine repository.
     */
    public function __construct(
        private readonly PasswordResetTokenRepository $repo
    ) {}

    /**
     * Invalidate all unused tokens for the given user by setting their `usedAt` timestamp.
     *
     * @param User $user The user whose tokens should be invalidated.
     * @return int Number of rows updated.
     */
    public function invalidateAllForUser(User $user): int
    {
        return $this->repo->invalidateAllForUser($user);
    }

    /**
     * Find a token for the given user that matches the provided hash and is still usable.
     *
     * Usable means:
     *  - Belongs to the given user.
     *  - Token hash matches.
     *  - `usedAt` is NULL.
     *  - Not expired according to entity logic.
     *
     * @param User   $user      The user who owns the token.
     * @param string $tokenHash SHA-256 hash of the plain token string.
     * @return PasswordResetToken|null The matching usable token, or null if none found.
     */
    public function findUsable(User $user, string $tokenHash): ?PasswordResetToken
    {
        return $this->repo->findUsable($user, $tokenHash);
    }
}
