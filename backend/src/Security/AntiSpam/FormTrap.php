<?php
declare(strict_types=1);

namespace App\Security\AntiSpam;

use Symfony\Component\HttpFoundation\Request;

/**
 * Simple anti-spam checks for public forms (honeypot + elapsed time).
 */
final class FormTrap
{
    /** @var int Minimum milliseconds a human typically needs before submitting. */
    private int $minElapsedMs;

    public function __construct(int $minElapsedMs = 1200)
    {
        $this->minElapsedMs = $minElapsedMs;
    }

    /**
     * Returns true if the request looks like a bot submission.
     *
     * @param array<string,mixed> $payload
     */
    public function isSuspicious(Request $request, array $payload): bool
    {
        // Honeypot (common bot pattern: fill every input)
        $hp = (string)($payload['hp'] ?? '');
        if (trim($hp) !== '') {
            return true;
        }

        // Time trap (if client provides elapsedMs)
        $elapsed = (int)($payload['elapsedMs'] ?? 0);
        return $elapsed > 0 && $elapsed < $this->minElapsedMs;
    }
}
