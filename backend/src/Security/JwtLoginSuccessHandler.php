<?php
declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * On successful username/password auth:
 * - If 2FA is enabled → return requires2fa + preToken (no final JWT yet).
 * - Else → return final JWT (keep existing behavior).
 */
final class JwtLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        /** @var User $user */
        $user = $token->getUser();

        if ($user->isTwoFactorEnabled()) {
            // Pre-token with a special claim. Keep its TTL short via Lexik config if needed.
            $preToken = $this->jwtManager->createFromPayload($user, ['two_factor' => 'pending']);

            return new JsonResponse([
                'requires2fa' => true,
                'preToken'    => $preToken,
            ], 200);
        }

        // Default path: issue final JWT (your previous behavior)
        $final = $this->jwtManager->create($user);

        return new JsonResponse([
            'token' => $final,
        ], 200);
    }
}
