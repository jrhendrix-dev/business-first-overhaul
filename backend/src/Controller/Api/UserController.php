<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\DTO\UpdateUserDTO;
use App\Repository\UserRepository;
use App\Service\UserManager;
use App\Helper\RoleRequestTrait;
use Doctrine\DBAL\Exception\DriverException as DbalDriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException as DbalUniqueViolation;
use Doctrine\DBAL\Exception\ConstraintViolationException as DbalConstraintViolation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * User-facing endpoints (self service + safe reads).
 */
#[IsGranted('ROLE_USER')]
#[Route('/api/users', name: 'users_')]
final class UserController extends AbstractController
{
    use RoleRequestTrait;

    public function __construct(
        private UserManager $userManager,
        private UserRepository $userRepository,
    ) {}

    /**
     * Public/user search (optional role filter).
     * Adjust access rules as needed for your app.
     */
    #[Route('', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $name = (string) $request->query->get('name', '');
        $roleEnum = $this->getRoleEnumFromRequest($request); // optional
        $users = $roleEnum ? $this->userManager->getUserByName($name, $roleEnum)
            : $this->userManager->getUserByName($name);

        if (!$users) {
            return $this->json(['message' => "No users found matching '$name'"], 404);
        }
        return $this->json($users, 200, [], ['groups' => 'user:read']);
    }

    #[Route('/{id}', name: 'get_by_id', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getById(int $id): JsonResponse
    {
        $user = $this->userManager->getUserById($id);
        return $user
            ? $this->json($user, 200, [], ['groups' => 'user:read'])
            : $this->json(['error' => 'User not found'], 404);
    }

    #[Route('/by-email', name: 'get_by_email', methods: ['GET'])]
    public function getByEmail(Request $request): JsonResponse
    {
        $email = (string) $request->query->get('email', '');
        return $this->json($this->userManager->getUserByEmail($email), 200, [], ['groups' => 'user:read']);
    }

    /**
     * Update the current (or specific) user.
     * Non-admins may only update themselves and cannot change roles.
     */
    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(
        int $id,
        Request $request,
        ValidatorInterface $validator
    ): JsonResponse {
        $user = $this->userManager->getUserById($id);
        if (!$user) {
            return $this->json(['error' => 'User not found.'], 404);
        }

        /** @var User|null $actor */
        $actor = $this->getUser();
        $isAdmin = $actor && $actor->getRole()->value === UserRoleEnum::ADMIN->value;

        if (!$isAdmin && $actor?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $dto = new UpdateUserDTO();
        $dto->firstName = $data['first_name'] ?? null;
        $dto->lastName  = $data['last_name']  ?? null;
        $dto->email     = $data['email']      ?? null;
        $dto->username  = $data['username']   ?? null;
        $dto->password  = $data['password']   ?? null;
        $dto->role      = $isAdmin ? ($data['role'] ?? null) : null; // users cannot change role

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $out = [];
            foreach ($errors as $v) { $out[] = ['field' => $v->getPropertyPath(), 'message' => $v->getMessage()]; }
            return $this->json(['errors' => $out], 400);
        }

        try {
            $updated = $this->userManager->updateUser($user, $dto);
        } catch (DbalUniqueViolation|DbalConstraintViolation|DbalDriverException|\PDOException $e) {
            if ((method_exists($e, 'getSQLState') && $e->getSQLState() === '23000') ||
                ($e instanceof \PDOException && $e->getCode() === '23000')) {
                return $this->json(['error' => 'Email or username already in use.'], 409);
            }
            return $this->json(['error' => 'Unable to update user.'], 500);
        }

        return $this->json($updated, 200, [], ['groups' => 'user:read']);
    }

    #[Route('/change-password', name: 'change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        Security $security
    ): JsonResponse {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $currentPassword = $data['current_password'] ?? null;
        $newPassword     = $data['new_password'] ?? null;
        $confirmPassword = $data['confirm_password'] ?? null;

        /** @var User|null $user */
        $user = $security->getUser();

        if (!$user) {
            return $this->json(['error' => 'User not found.'], 401);
        }
        if (!$currentPassword || !$newPassword) {
            return $this->json(['error' => 'Both current and new passwords are required.'], 400);
        }
        if ($newPassword !== ($confirmPassword ?? null)) {
            return $this->json(['error' => 'New password and confirmation do not match'], 400);
        }
        if (strlen($newPassword) < 8) {
            return $this->json(['error' => 'Password must be at least 8 characters long'], 400);
        }
        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            return $this->json(['error' => 'Current password is incorrect.'], 401);
        }
        if ($passwordHasher->isPasswordValid($user, $newPassword)) {
            return $this->json(['error' => 'New password must be different from the current password'], 400);
        }

        $newHash = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($newHash);
        $this->userManager->changePassword($user, $newHash);

        return $this->json(['message' => 'Password updated successfully.'], 200);
    }

    #[Route('/change-email', name: 'change_email', methods: ['POST'])]
    public function changeEmail(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        Security $security
    ): JsonResponse {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $email    = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        /** @var User|null $user */
        $user = $security->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not found.'], 401);
        }
        if (!$email || !$password) {
            return $this->json(['error' => 'Email and password are required.'], 400);
        }
        if ($email === $user->getEmail()) {
            return $this->json(['error' => 'The new email must be different from the current one.'], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Invalid email format.'], 400);
        }
        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Password is incorrect.'], 401);
        }

        $exists = $this->userRepository->findOneBy(['email' => $email]);
        if ($exists && $exists->getId() !== $user->getId()) {
            return $this->json(['error' => 'Email is already in use.'], 409);
        }

        $this->userManager->changeEmail($user, $email);
        return $this->json(['message' => 'Email updated successfully.'], 200);
    }
}
