<?php
declare(strict_types=1);

namespace App\Service\Security;

use App\Entity\User;
use OTPHP\TOTP;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * TOTP generation/verification + QR rendering (SVG data URL, no GD/Imagick).
 */
final class TotpService
{
    public function __construct(private readonly string $issuer = 'BusinessFirst') {}

    /** Generate a new Base32 secret. */
    public function generateSecret(): string
    {
        return TOTP::create()->getSecret();
    }

    /** Build an otpauth:// URI for authenticator apps. */
    public function buildOtpAuthUri(string $label, string $secret): string
    {
        $totp = TOTP::createFromSecret($secret);
        $totp->setLabel($label);
        $totp->setIssuer($this->issuer);
        return $totp->getProvisioningUri();
    }

    /** Verify a TOTP code given the raw secret. */
    public function verifySecret(string $secret, string $code): bool
    {
        $clean = preg_replace('/\D/', '', $code) ?? '';
        if ($clean === '') {
            return false;
        }

        $totp = TOTP::createFromSecret($secret);
        // positional: verify($otp, $timestamp = null, $window = 1)
        return $totp->verify($clean, null, 1);
    }

    /** Convenience wrapper: verify code for a User. */
    public function verifyUser(User $user, string $code): bool
    {
        $secret = (string) $user->getTotpSecret();
        return $secret !== '' && $this->verifySecret($secret, $code);
    }

    /** Render otpauth URI as SVG data URL. */
    public function qrDataUrl(string $otpauthUri): string
    {
        $result = Builder::create()
            ->writer(new SvgWriter())
            ->data($otpauthUri)
            ->size(300)
            ->margin(6)
            ->build();

        return 'data:image/svg+xml;base64,' . base64_encode($result->getString());
    }

    /**
     * Generate one-time recovery codes (plain + hashed).
     * @return array{plain: string[], hashed: string[]}
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $plain = [];
        for ($i = 0; $i < $count; $i++) {
            $plain[] = sprintf(
                '%04s-%04s-%04s',
                bin2hex(random_bytes(2)),
                bin2hex(random_bytes(2)),
                bin2hex(random_bytes(2))
            );
        }

        $hashed = array_map(
            static fn (string $c) => password_hash($c, PASSWORD_ARGON2ID),
            $plain
        );

        return ['plain' => $plain, 'hashed' => $hashed];
    }

    /**
     * If $candidate matches one of the hashed codes, remove it and return the updated list.
     * Returns null if no match.
     *
     * @param string[] $hashedList
     * @return string[]|null
     */
    public function consumeRecoveryCode(array $hashedList, string $candidate): ?array
    {
        foreach ($hashedList as $i => $hash) {
            if (password_verify($candidate, $hash)) {
                unset($hashedList[$i]);
                return array_values($hashedList);
            }
        }
        return null;
    }
}
