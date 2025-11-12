<?php
declare(strict_types=1);

namespace App\Controller\Auth;

use App\Entity\User;
use App\Http\Exception\ValidationException;
use App\Service\Security\TotpService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth/2fa')]
final class TwoFactorController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
        private readonly TotpService $totp,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly JWTEncoderInterface $jwtEncoder,
    ) {}

    #[Route('/verify', name: 'api_auth_2fa_verify', methods: ['POST'])]
    public function verify(Request $request): JsonResponse
    {
        /** @var array<string,mixed> $body */
        $body = json_decode($request->getContent() ?: '{}', true) ?? [];

        $preToken = (string)($body['preToken'] ?? '');
        $code     = isset($body['code']) ? (string)$body['code'] : null;
        $recovery = isset($body['recoveryCode']) ? (string)$body['recoveryCode'] : null;

        $errors = [];
        if ($preToken === '') {
            $errors['preToken'] = 'Required';
        }
        if ($code === null && $recovery === null) {
            $errors['totp'] = 'Either code or recoveryCode required';
        }
        if ($errors) {
            throw new ValidationException($errors);
        }

        $payload = $this->jwtEncoder->decode($preToken);
        if (!$payload || ($payload['two_factor'] ?? null) !== 'pending') {
            throw new HttpException(401, 'AUTH_FAILED');
        }

        $identifier = $payload['username'] ?? $payload['email'] ?? $payload['sub'] ?? null;
        if (!$identifier) {
            throw new HttpException(401, 'AUTH_FAILED');
        }

        /** @var User|null $user */
        $repo = $this->em->getRepository(User::class);
        $user = $repo->findOneBy(['email' => $identifier]) ?? $repo->findOneBy(['username' => $identifier]);

        if (!$user instanceof User || !$user->isTwoFactorEnabled() || !$user->getTotpSecret()) {
            throw new HttpException(401, 'AUTH_FAILED');
        }

        if ($code !== null) {
            if (!$this->totp->verifySecret($user->getTotpSecret(), $code)) {
                throw new ValidationException(['totp' => 'Invalid code']);
            }
        } else {
            $updated = $this->totp->consumeRecoveryCode(
                $user->getTwoFactorRecoveryCodes(),
                (string) $recovery
            );
            if ($updated === null) {
                throw new ValidationException(['recoveryCode' => 'Invalid or already used']);
            }
            $user->setTwoFactorRecoveryCodes($updated);
            $this->em->flush();
        }

        $token = $this->jwtManager->create($user);
        $user->setLast2faVerifiedAt(new \DateTimeImmutable());
        $this->em->flush();

        return new JsonResponse(['token' => $token], 200);
    }

    #[Route('/setup/initiate', name: 'api_auth_2fa_setup_initiate', methods: ['GET'])]
    public function initiate(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedException();
        }

        // If user already has a secret, reuse it (don’t rotate silently)
        $secret = $user->getTotpSecret();
        if (!$secret) {
            $secret = $this->totp->generateSecret();
            $user->setTotpSecret($secret);
            $this->em->flush();
        }

        $label     = $user->getEmail() ?: $user->getUserIdentifier();
        $otpauth   = $this->totp->buildOtpAuthUri($label, $secret);
        $qrDataUrl = $this->totp->qrDataUrl($otpauth);

        return new JsonResponse([
            'secret'     => $secret,
            'otpauthUri' => $otpauth,
            'qrPng'      => $qrDataUrl,
        ], 200);
    }

    #[Route('/setup/confirm', name: 'api_auth_2fa_setup_confirm', methods: ['POST'])]
    public function confirm(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new HttpException(401, 'AUTH_FAILED');
        }
        if (!$user->getTotpSecret()) {
            throw new HttpException(400, 'TOTP_SECRET_MISSING');
        }

        /** @var array<string,mixed> $body */
        $body = json_decode($request->getContent() ?: '{}', true) ?? [];
        $code = (string)($body['code'] ?? '');

        if ($code === '') {
            throw new ValidationException(['totp' => 'Code required']);
        }
        if (!$this->totp->verifySecret($user->getTotpSecret(), $code)) {
            throw new ValidationException(['totp' => 'Invalid code']);
        }

        $user->enableTwoFactor();
        $pair = $this->totp->generateRecoveryCodes(8);
        $user->setTwoFactorRecoveryCodes($pair['hashed']);
        $this->em->flush();

        return new JsonResponse([
            'enabled'       => true,
            'recoveryCodes' => $pair['plain'],
        ], 200);
    }

    #[Route('/disable', name: 'api_auth_2fa_disable', methods: ['POST'])]
    public function disable(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new HttpException(401, 'AUTH_FAILED');
        }

        $user->disableTwoFactor();
        $user->setTotpSecret(null);
        $user->setTwoFactorRecoveryCodes([]);
        $this->em->flush();

        return new JsonResponse(['enabled' => false], 200);
    }

    #[Route('/recovery/regenerate', name: 'api_auth_2fa_recovery_regenerate', methods: ['POST'])]
    public function regenerate(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || !$user->isTwoFactorEnabled()) {
            throw new HttpException(401, 'AUTH_FAILED');
        }

        $pair = $this->totp->generateRecoveryCodes(8);
        $user->setTwoFactorRecoveryCodes($pair['hashed']);
        $this->em->flush();

        return new JsonResponse(['recoveryCodes' => $pair['plain']], 200);
    }
}
