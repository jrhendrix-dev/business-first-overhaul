<?php
declare(strict_types=1);

namespace App\Security;

use App\Dto\User\UserResponseDto;
use App\Mapper\Response\UserResponseMapper;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builds the login JSON payload: { token, user: UserResponseDto }
 */
final class JwtLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UserResponseMapper $userMapper,
    ) {}

    /** @inheritDoc */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        /** @var \App\Entity\User $user */
        $user  = $token->getUser();
        $jwt   = $this->jwtManager->create($user);
        /** @var UserResponseDto $dto */
        $dto   = $this->userMapper->toResponse($user);

        return new JsonResponse([
            'token' => $jwt,
            'user'  => $dto,
        ], Response::HTTP_OK);
    }
}
