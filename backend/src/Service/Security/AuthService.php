<?php
declare(strict_types=1);

namespace App\Service\Security;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Small helper to access the currently authenticated user.
 */
final class AuthService
{
    public function __construct(private Security $security) {}

    /** Logged-in user or null. */
    public function user(): ?User
    {
        $u = $this->security->getUser();
        return $u instanceof User ? $u : null;
    }

    /** Logged-in user id or null. */
    public function id(): ?int
    {
        return $this->user()?->getId();
    }

    /** Require a logged-in user (throws if missing). */
    public function require(): User
    {
        $u = $this->user();
        if (!$u) {
            throw new \RuntimeException('Not authenticated');
        }
        return $u;
    }
}
