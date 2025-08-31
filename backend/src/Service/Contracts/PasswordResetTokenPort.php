<?php
declare(strict_types=1);

namespace App\Service\Contracts;

use App\Entity\PasswordResetToken;
use App\Entity\User;

/**
 * Narrow contract used by PasswordResetManager.
 */
interface PasswordResetTokenPort
{
    /**
     * Invalidate all old tokens for a user (optional hardening).
     */
    public function invalidateAllForUser(User $user): int;

    /**
     * Find a usable (unused, not expired) token for the given user and hash.
     */
    public function findUsable(User $user, string $tokenHash): ?PasswordResetToken;
}
