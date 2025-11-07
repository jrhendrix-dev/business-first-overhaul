<?php
declare(strict_types=1);

namespace App\Service\Auth;

use Google\Client as GoogleClient;

/**
 * Verifies Google ID tokens and returns basic profile data or null.
 */
final class GoogleIdTokenVerifier
{
    public function __construct(
        private readonly GoogleClient $googleClient,
        private readonly string $expectedClientId
    ) {
        $this->googleClient->setClientId($expectedClientId);
    }

    /** @return array{id:string,email:string,name?:string,picture?:string}|null */
    public function verify(string $idToken): ?array
    {
        $p = $this->googleClient->verifyIdToken($idToken);
        if (!$p) return null;
        if (($p['aud'] ?? null) !== $this->expectedClientId) return null;
        if (!in_array($p['iss'] ?? '', ['https://accounts.google.com','accounts.google.com'], true)) return null;
        if (($p['email_verified'] ?? false) !== true) return null;

        return [
            'id'      => (string)($p['sub'] ?? ''),
            'email'   => (string)($p['email'] ?? ''),
            'name'    => $p['name'] ?? null,
            'picture' => $p['picture'] ?? null,
        ];
    }
}
