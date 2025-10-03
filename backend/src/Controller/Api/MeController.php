<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\User\Password\ChangePasswordDto;
use App\Dto\User\UpdateUserDto;
use App\Entity\User;
use App\Http\Exception\ValidationException;
use App\Mapper\Request\MeChangeEmailRequestMapper;
use App\Mapper\Request\MeChangePasswordRequestMapper;
use App\Mapper\Request\MeForgotPasswordRequestMapper;
use App\Mapper\Request\MeResetPasswordRequestMapper;
use App\Mapper\Request\UserUpdateRequestMapper;
use App\Mapper\Response\MeResponseMapper;
use App\Mapper\Response\UserResponseMapper;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\User as AppUser;

#[IsGranted('ROLE_USER')]
#[Route('/me', name: 'me_')]
final class MeController extends AbstractController
{
    public function __construct(
        private readonly Security $security,
        private readonly UserManager $users,
        private readonly UserResponseMapper $toResponse,     // full user projection (used for PATCH)
        private readonly MeResponseMapper $toMeResponse,     // minimal self view
        private readonly UserManager $manager,
        private readonly UserUpdateRequestMapper $updateMapper,
        private readonly MeChangePasswordRequestMapper $pwdMapper,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('', name: 'get', methods: ['GET'])]
    public function getSelf(): JsonResponse
    {
        $user = $this->requireAuthenticatedUserEntity();
        return $this->json($this->toMeResponse->toResponse($user)->toArray(), Response::HTTP_OK);
    }

    #[Route('', name: 'update', methods: ['PATCH'])]
    public function update(Request $request): JsonResponse
    {
        $user = $this->requireAuthenticatedUserEntity();

        /** @var UpdateUserDto $dto */
        $dto = $this->updateMapper->fromRequest($request);
        $dto->role     = null; // never writable via /me
        $dto->email    = null;
        $dto->password = null;

        $violations = $this->validator->validate($dto);
        if (\count($violations) > 0) {
            $details = [];
            foreach ($violations as $v) { $details[$v->getPropertyPath()] = $v->getMessage(); }
            throw new ValidationException($details);
        }

        $updated = $this->users->updateUser($user, $dto);
        return $this->json($this->toResponse->toResponse($updated), Response::HTTP_OK);
    }

    /**
     * Body: { "currentPassword": "...", "newPassword": "...", "confirmPassword": "..." }
     */
    #[Route('/change-password', name: 'change_password', methods: ['POST'])]
    public function changePassword(Request $request): JsonResponse
    {
        $user = $this->requireAuthenticatedUserEntity();

        /** @var ChangePasswordDto $dto */
        $dto = $this->pwdMapper->fromRequest($request);

        // Keep the request-shape validation here (DTO constraints / confirm match)
        $violations = $this->validator->validate($dto);
        if (\count($violations) > 0) {
            $details = [];
            foreach ($violations as $v) { $details[$v->getPropertyPath()] = $v->getMessage(); }
            throw new ValidationException($details);
        }
        if ($dto->newPassword !== $dto->confirmPassword) {
            throw new ValidationException(['confirmPassword' => 'Must match newPassword']);
        }

        // Delegate the sensitive checks + persistence + email notification
        $this->manager->changeOwnPassword($user, $dto);

        return $this->json(['message' => 'Password updated successfully.'], Response::HTTP_OK);
    }

    /**
     * Body: { "newEmail": "new@example.com", "password": "current_password" }
     * Sends a confirmation email with a token. No immediate change.
     */
    #[Route('/change-email', name: 'me_email_change_start', methods: ['POST'])]
    public function startChangeEmail(Request $req): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $data     = \json_decode($req->getContent() ?: '{}', true, 512, \JSON_THROW_ON_ERROR);
        $newEmail = (string)($data['newEmail'] ?? $data['new_email'] ?? '');
        $password = (string)($data['password'] ?? '');

        if ($newEmail === '' || $password === '') {
            throw new ValidationException(['newEmail' => 'Required', 'password' => 'Required']);
        }

        try {
            $this->users->startEmailChange($user, $newEmail, $password);
        } catch (\DomainException $e) {
            return $this->json(
                ['error' => ['code' => 'VALIDATION_FAILED', 'details' => ['message' => $e->getMessage()]]],
                Response::HTTP_CONFLICT
            );
        }

        return $this->json(['message' => 'Confirmation email sent'], Response::HTTP_OK);
    }

    /**
     * Confirm email change with a token (from Mailtrap link).
     * GET /api/me/change-email/confirm?token=...
     */
    #[Route('/change-email/confirm', name: 'me_email_change_confirm', methods: ['GET'])]
    public function confirmChangeEmail(Request $req): JsonResponse
    {
        $token = (string)$req->query->get('token', '');
        if ($token === '') {
            throw new ValidationException(['token' => 'Required']);
        }

        try {
            $user = $this->users->confirmEmailChange($token);
        } catch (\DomainException $e) {
            return $this->json(
                ['error' => ['code' => 'INVALID_TOKEN', 'details' => ['message' => $e->getMessage()]]],
                Response::HTTP_CONFLICT
            );
        }

        return $this->json([
            'message' => 'Email updated. Please sign in again.',
            'email'   => $user->getEmail(),
        ], Response::HTTP_OK);
    }


    /**
     * Ensure the authenticated principal is our concrete App\Entity\User.
     * @return AppUser
     */
    private function requireAuthenticatedUserEntity(): AppUser
    {
        $user = $this->security->getUser();
        if (!$user instanceof AppUser) {
            // Either anonymous, or a different User class from another firewall/provider
            throw $this->createAccessDeniedException();
        }
        return $user;
    }
}
