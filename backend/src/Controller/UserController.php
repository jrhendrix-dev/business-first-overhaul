<?php

namespace App\Controller;


use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\DTO\CreateUserDTO;

use App\Repository\UserRepository;
use App\Service\ClassroomManager;
use App\Service\EnrollmentManager;
use App\Service\UserManager;
use App\Helper\RoleRequestTrait;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;


/**
 * UserController handles API endpoints for user management.
 * Provides operations for user CRUD, role changes, password/email updates, and user search.
 * Requires appropriate security roles for certain operations.
 */
#[Route('/api/users')]
class UserController extends AbstractController
{
    use RoleRequestTrait;

    /**
     * UserController constructor.
     *
     * @param EntityManagerInterface $em Doctrine entity manager for database operations
     * @param UserManager $userManager Service for user management operations
     * @param ClassroomManager $classroomManager Service for classroom-related operations
     */
    public function __construct(
        private UserManager $userManager,
        private ClassroomManager $classroomManager,
        private UserRepository $userRepository,

    )
    {
    }

    /**
     * Returns a list of all users.
     *
     * @return JsonResponse JSON response containing user data
     * @throws \JsonException If serialization fails
     */
    #[Route('', name: 'users_list', methods: ['GET'])]
    public function getAllUsers(): JsonResponse
    {
        $users = $this->userManager->getAllUsers();
        return $this->json(
            $users,
            200,
            [],
            ['groups' => 'user:read']
        );
    }

    /**
     * Returns a list of all student users.
     *
     * @return JsonResponse JSON response containing student data
     * @throws \JsonException If serialization fails
     */
    #[Route('/students', name: 'students_list', methods: ['GET'])]
    public function getAllStudents(): JsonResponse
    {
        $students = $this->userManager->getAllStudents();
        return $this->json(
            $students,
            200,
            [],
            ['groups' => 'user:read']
        );
    }

    /**
     * Returns a list of all teacher users.
     *
     * @return JsonResponse JSON response containing teacher data
     * @throws \JsonException If serialization fails
     */
    #[Route('/teachers', name: 'teachers_list', methods: ['GET'])]
    public function getAllTeachers(): JsonResponse
    {
        $teachers = $this->userManager->getAllTeachers();
        return $this->json(
            $teachers,
            200,
            [],
            ['groups' => 'user:read']
        );
    }

    /**
     * Searches for users by name with optional role filter.
     * Uses the RoleRequestTrait to extract role parameter.
     *
     * @param Request $request The HTTP request containing 'name' and optional 'role' parameters
     * @return JsonResponse JSON response with search results or error message
     * @throws \JsonException If request content is invalid JSON
     */
    #[Route('/get-user-by-name', name: 'user_get_by_name', methods: ['GET'])]
    public function getUserByName(Request $request): JsonResponse
    {
        $name = $request->query->get('name');
        $role = $request->query->get('role'); // optional, if needed

        $roleEnum = $this->getRoleEnumFromRequest($request);

        if (!$role) {
            $users = $this->userManager->getUserByName($name);
        } else {
            $users = $this->userManager->getUserByName($name, $roleEnum);
        }

        if ($users === null) {
            return $this->json([
                'message' => "No users found matching '$name'",
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json($users, Response::HTTP_OK, [], ['groups' => 'user:read']);
    }

    /**
     * Retrieves a user by their ID.
     *
     * @param Request $request The HTTP request containing the 'id' query parameter
     * @return JsonResponse JSON response with user data or error message
     */
    #[Route('/get-user-by-id', name: 'user_get_by_id', methods: ['GET'])]
    public function getUserById(Request $request): JsonResponse
    {
        $id = $request->query->get('id');
        $user = $this->userManager->getUserById($id);
        return $this->json($user, Response::HTTP_OK, [], ['groups' => 'user:read']);
    }

    /**
     * Retrieves a user by their email address.
     *
     * @param Request $request The HTTP request containing the 'email' query parameter
     * @return JsonResponse JSON response with user data or error message
     */
    #[Route('/get-user-by-email', name: 'user_get_by_email', methods: ['GET'])]
    public function getUserByEmail(Request $request): JsonResponse
    {
        $email = $request->query->get('email');
        $user = $this->userManager->getUserByEmail($email);
        return $this->json($user, Response::HTTP_OK, [], ['groups' => 'user:read']);
    }


    /**
     * Retrieves users registered within the last specified number of days.
     *
     * @param Request $request The HTTP request containing optional 'days' parameter (default: 30)
     * @return JsonResponse JSON response with recently registered users
     * @throws \JsonException If request content is invalid JSON
     */
    #[Route('/get-recently-registered', name: 'user_recently_registered', methods: ['GET'])]
    public function getUserRecentlyRegistered(Request $request): JsonResponse
    {
        $days = $request->query->get('days', 30);

        if ($days <= 0) {
            return $this->json([
                'error' => "'days' must be a positive integer"
            ], Response::HTTP_BAD_REQUEST);
        }

        $users = $this->userManager->getRecentlyRegistered($days);
        return $this->json($users, Response::HTTP_OK, [], ['groups' => 'user:read']);
    }

    /**
     * Retrieves students not assigned to any classroom.
     *
     * @return JsonResponse JSON response with unassigned student data
     * @throws \JsonException If serialization fails
     */
    #[Route('/get-unassigned-students', name: 'user_get_unassigned_students', methods: ['GET'])]
    public function getStudentsUnassigned(): JsonResponse
    {
        $rows = array_map(
            static fn(User $u) => [
                'id'       => $u->getId(),
                'username' => $u->getUsername(),
                'email'    => $u->getEmail(),
            ],
            $this->userRepository->findUnassignedStudents()
        );

        return $this->json($rows, Response::HTTP_OK);
    }

    /**
     * Retrieves teachers not assigned to any classroom.
     *
     * @return JsonResponse JSON response with unassigned teacher data
     * @throws \JsonException If serialization fails
     */
    #[Route('/get-unassigned-teachers', name: 'user_get_unassigned_teachers', methods: ['GET'])]
    public function getTeachersUnassigned(): JsonResponse
    {
        $rows = array_map(
            static fn(User $u) => [
                'id'       => $u->getId(),
                'username' => $u->getUsername(),
                'email'    => $u->getEmail(),
            ],
            $this->userRepository->findUnassignedTeachers()
        );

        return $this->json($rows, Response::HTTP_OK);
    }

    /**
     * Retrieves the count of users with a specific role.
     * Uses the RoleRequestTrait to extract the role parameter.
     *
     * @param Request $request The HTTP request containing the 'role' parameter
     * @return JsonResponse JSON response with user count or error message
     * @throws \JsonException If request content is invalid JSON
     */
    #[Route('/get-count-by-role', name: 'user_get_count_by_role', methods: ['GET'])]
    public function getCountByRole(Request $request): JsonResponse
    {
        $roleEnum = $this->getRoleEnumFromRequest($request);

        if (!$roleEnum) {
            return $this->json([
                'error' => 'Missing or invalid role'
            ], Response::HTTP_BAD_REQUEST);
        }

        $count = $this->userManager->getCountByRole($roleEnum);
        return $this->json(['count' => $count], Response::HTTP_OK, [], ['groups' => 'user:read']);
    }

    /**
     * Changes a user's password after validating current password.
     * Requires authentication and authorization.
     *
     * @param Request $request The HTTP request containing password data
     * @param UserPasswordHasherInterface $passwordHasher Service for password hashing
     * @param Security $security Security service to get the current user
     * @return JsonResponse JSON response with success message or error details
     * @throws \JsonException If request content is invalid JSON
     * @note Requires ROLE_ADMIN for non-self password changes
     */
    #[Route('/change-password', name: 'change_password', methods: ['POST'])]  //Works, pending: password history, password strength check
    public function changePassword(
        Request                     $request,
        UserPasswordHasherInterface $passwordHasher,
        Security                    $security
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $currentPassword = $data['current_password'] ?? null;
        $newPassword = $data['new_password'] ?? null;
        $confirmPassword = $data['confirm_password'] ?? null;

        /** @var User $user */
        $user = $security->getUser();

        if (!$currentPassword || !$newPassword) {
            return $this->json([
                'error' => 'Both current and new passwords are required.'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($newPassword !== ($confirmPassword ?? null)) {
            return $this->json(['error' => 'New password and confirmation do not match'], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($newPassword) < 8) {
            return $this->json(['error' => 'Password must be at least 8 characters long'], Response::HTTP_BAD_REQUEST);
        }

        if (!$user) {
            return $this->json([
                'error' => 'User not found.'
            ]);
        }

        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            return $this->json([
                'error' => 'Current password is incorrect.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($passwordHasher->isPasswordValid($user, $newPassword)) {
            return $this->json(['error' => 'New password must be different from the current password'], Response::HTTP_BAD_REQUEST);
        }

        $newHash = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($newHash);
        $this->userManager->changePassword($user, $newHash);

        return $this->json([
            'message' => 'Password updated successfully.'
        ], Response::HTTP_OK);
    }

    /**
     * Changes a user's email address after validating current password.
     * Requires authentication and authorization.
     *
     * @param Request $request The HTTP request containing email and password data
     * @param UserPasswordHasherInterface $passwordHasher Service for password validation
     * @param Security $security Security service to get the current user
     * @return JsonResponse JSON response with success message or error details
     * @throws \JsonException If request content is invalid JSON
     */
    #[Route('/change-email', name: 'change_email', methods: ['POST'])]
    public function changeEmail(
        Request                     $request,
        UserPasswordHasherInterface $passwordHasher,
        Security                    $security
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        /** @var User|null $user */
        $user = $security->getUser();

        if (!$user) {
            return $this->json(['error' => 'User not found.'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$email || !$password) {
            return $this->json(['error' => 'Email and password are required.'], Response::HTTP_BAD_REQUEST);
        }

        if ($email === $user->getEmail()) {
            return $this->json(['error' => 'The new email must be different from the current one.'], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Invalid email format.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Password is incorrect.'], Response::HTTP_UNAUTHORIZED);
        }

        $this->userManager->changeEmail($user, $email);
        return $this->json(['message' => 'Email updated successfully.'], Response::HTTP_OK);
    }

    /**
     * Changes a user's role (admin-only operation).
     * Uses the RoleRequestTrait to extract the role parameter.
     *
     * @param int $id The ID of the user to modify
     * @param Request $request The HTTP request containing the role parameter
     * @return JsonResponse JSON response with success message or error details
     * @throws \JsonException If request content is invalid JSON
     * @IsGranted("ROLE_ADMIN") Only accessible to users with admin role
     */
    #[Route('/change-role/{id}', name: 'change_role', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function changeRole(int $id, Request $request, EnrollmentManager $enrollments): JsonResponse
    {
        // Parse/validate role
        $roleEnum = $this->getRoleEnumFromRequest($request);
        if (!$roleEnum) {
            return $this->json([
                'error'     => 'Invalid or missing role.',
                'hint'      => 'Send ?role=<value> or {"role": "<value>"}',
                'valid_roles' => UserRoleEnum::values(),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Find target user
        $target = $this->userManager->getUserById($id);
        if (!$target) {
            return $this->json(['error' => 'Target user not found.'], Response::HTTP_NOT_FOUND);
        }

        // No-op if same role
        if ($target->getRole() === $roleEnum) {
            return $this->json([
                'message'  => 'User already has that role.',
                'user_id'  => $target->getId(),
                'role'     => $roleEnum->value,
            ], Response::HTTP_OK);
        }

        // Change the role (will drop active enrollments when moving away from STUDENT)
        $this->userManager->changeRole($target, $roleEnum, $enrollments);

        return $this->json([
            'message'  => 'Role updated.',
            'user_id'  => $target->getId(),
            'new_role' => $roleEnum->value,
        ], Response::HTTP_OK);
    }

    /**
     * Return unassigned students and teachers (no entity serialization, scalars only).
     */
    #[Route('/unassigned', name: 'users_unassigned', methods: ['GET'])]
    public function getUnassigned(): JsonResponse
    {
        $students = array_map(
            static fn(User $u) => [
                'id'       => $u->getId(),
                'username' => $u->getUsername(),
                'email'    => $u->getEmail(),
            ],
            $this->userRepository->findUnassignedStudents()
        );

        $teachers = array_map(
            static fn(User $u) => [
                'id'       => $u->getId(),
                'username' => $u->getUsername(),
                'email'    => $u->getEmail(),
            ],
            $this->userRepository->findUnassignedTeachers()
        );

        return $this->json(['students' => $students, 'teachers' => $teachers], Response::HTTP_OK);
    }

    /**
     * Creates a new user with the specified details.
     * Requires admin privileges.
     *
     * @param Request $request The HTTP request containing user data
     * @return JsonResponse JSON response with created user data or error message
     * @throws \JsonException
     */
    #[Route('/create-user', name: 'create_user', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createUser(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $dto = new CreateUserDTO();
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $dto->firstName = trim((string)($data['first_name'] ?? ''));
        $dto->lastName = trim((string)($data['last_name'] ?? ''));
        $dto->email = trim((string)($data['email'] ?? ''));
        $dto->username = trim((string)($data['username'] ?? ''));
        $dto->password = (string)($data['password'] ?? '');
        $dto->role = (int)($data['role'] ?? 0);

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $normalized = [];
            foreach ($errors as $violation) {
                $normalized[] = [
                    'field'   => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }
            return $this->json(['errors' => $normalized], 400);
        }

        $user = $this->userManager->createUser(
            $dto->firstName,
            $dto->lastName,
            $dto->email,
            $dto->username,
            $dto->password,
            UserRoleEnum::from($dto->role)
        );

        return $this->json($user, Response::HTTP_CREATED, [], ['groups' => 'user:read']);
    }

    /**
     * Deletes a user from the database.
     * Prevents users from deleting their own accounts.
     * Requires admin privileges.
     *
     * @param int $id The ID of the user to delete
     * @param UserManager $userManager Service for user deletion
     * @return JsonResponse JSON response with success status
     */
    #[Route('/remove-user/{id}', name: 'remove_user', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function removeUser(int $id, UserManager $userManager): JsonResponse
    {
        $target = $this->userManager->getUserById($id);

        if (!$target) {
            return $this->json(['error' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        // Prevent deleting yourself
        /** @var User $actor */
        $actor = $this->getUser();

        if ($actor && $actor->getId() === $target->getId()) {
            return $this->json(['error' => 'You cannot delete your own account.'], Response::HTTP_BAD_REQUEST);
        }

        // Perform delete
        $userManager->removeUser($target);
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
