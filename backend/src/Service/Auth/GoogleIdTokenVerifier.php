<?php
declare(strict_types=1);

namespace App\Service\Auth;

use Google\Client as GoogleClient;

final class GoogleIdTokenVerifier
{
    /** @var string[] */
    private array $expectedClientIds;

    public function __construct(
        private readonly GoogleClient $googleClient,
        string $expectedClientIdsCsv
    ) {
        $this->expectedClientIds = array_values(array_filter(array_map('trim', explode(',', $expectedClientIdsCsv))));
        if (isset($this->expectedClientIds[0])) {
            $this->googleClient->setClientId($this->expectedClientIds[0]);
        }
    }

    /** @return array{id:string,email:string,name?:string,picture?:string}|null */
    public function verify(string $idToken): ?array
    {
        $p = $this->googleClient->verifyIdToken($idToken);
        if (!$p) return null;

        $aud = (string)($p['aud'] ?? '');
        $iss = (string)($p['iss'] ?? '');

        if (!in_array($aud, $this->expectedClientIds, true)) {
            if ($_ENV['APP_ENV'] !== 'prod') {
                error_log('[GoogleIdTokenVerifier] audience mismatch aud=' . $aud);
            }
            return null;
        }
        if ($iss !== 'https://accounts.google.com' && $iss !== 'accounts.google.com') return null;
        if (($p['email_verified'] ?? false) !== true) return null;

        return [
            'id'      => (string)($p['sub'] ?? ''),
            'email'   => (string)($p['email'] ?? ''),
            'name'    => $p['name'] ?? null,
            'picture' => $p['picture'] ?? null,
        ];
    }
}
