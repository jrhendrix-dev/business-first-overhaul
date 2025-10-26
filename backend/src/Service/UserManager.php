<?php

namespace App\Service;

use App\Dto\User\UpdateUserDTO;
use App\Entity\Classroom;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Repository\UserRepository;
use App\Service\Contracts\EnrollmentPort;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Enum\AccountTokenType;
use App\Dto\User\Password\ChangePasswordDto;
use App\Dto\User\Password\ForgotPasswordDto;
use App\Dto\User\Password\ResetPasswordDto;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\EmailChangeConfirmMessage;
use App\Message\EmailChangeNotifyOldMessage;
use App\Http\Exception\ValidationException;
use App\Message\WelcomeEmailMessage;

/**
 * Service responsible for managing business logic related to User entities.
 * Provides methods for user creation, modification, retrieval, and role management.
 */
class UserManager
{
    /**
     * UserManager constructor.
     *
     * @param EntityManagerInterface $em The Doctrine entity manager for database operations
     * @param UserPasswordHasherInterface $passwordHasher Service for password hashing
     * @param UserRepository $userRepository Repository for user data access
     */
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
        private AccountTokenService $tokens,
        private AccountMailer $mailer,
        private readonly MessageBusInterface $bus,
        private readonly EnrollmentManager $enrollments,
        private readonly ClassroomManager $classrooms,
    )
    {
    }

    /**
     * Retrieves all users from the database.
     *
     * @return User[] Array of User entities
     */
    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    /**
     * Retrieves all student users from the database.
     *
     * @return User[] Array of student User entities
     */
    public function getAllStudents(): array
    {
        return $this->userRepository->findAllStudents();
    }

    /**
     * Retrieves all teacher users from the database.
     *
     * @return User[] Array of teacher User entities
     */
    public function getAllTeachers(): array
    {
        return $this->userRepository->findAllTeachers();
    }

    /**
     * Handle all side-effects of moving between roles.
     *
     * Rules:
     *  - FROM STUDENT → anything: drop all active enrollments, detach classrooms seat.
     *  - TO   STUDENT ← from TEACHER/ADMIN: unassign as teacher from all classrooms.
     *  - NO-OP if roles are the same.
     *
     * @param User          $user
     * @param UserRoleEnum  $from
     * @param UserRoleEnum  $to
     */
    private function applyRoleTransition(User $user, UserRoleEnum $from, UserRoleEnum $to): void
    {
        // Moving away from STUDENT
        if ($from === UserRoleEnum::STUDENT && $to !== UserRoleEnum::STUDENT) {
            // Domain invariants: a non-student must not keep student enrollments.
            $this->enrollments->dropAllActiveForStudent($user);
            $this->classrooms->detachStudentFromAnyClassroom($user);
        }

        // Moving into STUDENT
        if ($to === UserRoleEnum::STUDENT && $from !== UserRoleEnum::STUDENT) {
            // A student must not be a teacher anywhere.
            $this->classrooms->unassignTeacherFromAll($user);
        }
    }

    /**
     * Persist a new hashed password for the given user.
     * Expects a **hashed** password string.
     */
    public function changePassword(User $user, string $hashedPassword): void
    {
        $user->setPassword($hashedPassword);
        $this->em->persist($user);
        $this->em->flush();
    }

    /**
     * Updates a user's email address.
     *
     * @param User $user The user to modify
     * @param string $email The new email address
     */
    public function changeEmail(User $user, string $email): void
    {
        $user->setEmail($email);
        $this->em->persist($user);
        $this->em->flush();
    }

    /**
     * Retrieves a user by their ID.
     *
     * @param int $id The user's unique identifier
     * @return User|null The found User entity or null if not found
     */
    public function getUserById(int $id): ?User
    {
        return $this->userRepository->findUserById($id);
    }

    /**
     * Retrieves a user by their email address.
     *
     * @param string $email The user's email address
     * @return User|null The found User entity or null if not found
     */
    public function getUserByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    /**
     * Searches for users by name with an optional role filter.
     *
     * @param string $name The name to search for
     * @param UserRoleEnum|null $role Optional role filter
     * @return array|null Array of User entities matching the criteria or null if none found
     */
    public function getUserByName(string $name, ?UserRoleEnum $role = null): ?array
    {
        return $this->userRepository->searchByName($name, $role);
    }


    /**
     * searches for users but with pagination
     *
     * @param string $q
     * @param UserRoleEnum|null $role
     * @param int $page
     * @param int $size
     * @return array
     */
    public function searchUsers(string $q, ?UserRoleEnum $role, int $page, int $size): array
    {
        return $this->userRepository->searchPaginated($q, $role, $page, $size);
    }


    /**
     * Verifies if a student belongs to a specific classrooms.
     *
     * @param int $studentId The student's ID
     * @param int $classroomId The classrooms's ID
     * @return User|null The student entity if found in the classrooms, null otherwise
     */
    public function getUserInClassroom(int $studentId, int $classroomId): ?User
    {
        return $this->userRepository->findStudentInClassroom($studentId, $classroomId);
    }

    /**
     * Retrieves users registered within the last specified number of days.
     *
     * @param int $days Number of days to look back (default: 30)
     * @return array|null Array of recently registered User entities or null if none found
     */
    public function getRecentlyRegistered(int $days = 30): ?array
    {
        return $this->userRepository->findRecentlyRegisteredUsers($days);
    }

    /**
     * Retrieves students not assigned to any classrooms.
     *
     * @return User[] Array of student User entities without classrooms assignments
     */
    public function getStudentsWithoutClassroom(): ?array
    {
        return $this->userRepository->findStudentsWithoutClassroom();
    }

    /**
     * Retrieves teachers not assigned to any classrooms.
     *
     * @return User[] Array of teacher User entities without classrooms assignments
     */
    public function getTeachersWithoutClassroom(): ?array
    {
        return $this->userRepository->findTeachersWithoutClassroom();
    }

    /**
     * Counts users with a specific role.
     *
     * @param UserRoleEnum $role The role to count
     * @return int Number of users with the specified role
     */
    public function getCountByRole(UserRoleEnum $role): int
    {
        return $this->userRepository->countByRole($role);
    }

    /**
     * Unassigns all students from a classrooms and clears the entity manager cache.
     *
     * @param Classroom $classroom The classrooms to unassign students from
     * @return int Number of students unassigned
     */
    public function unassignAllStudentsFromClassroom(Classroom $classroom): int
    {
        // Fast path: bulk update
        $count = $this->userRepository->unassignAllFromClassroom($classroom);
        $this->em->clear(User::class); // keep the EM consistent
        return $count;
    }

    /**
     * Creates a new user with the specified details.
     *
     * @param string $firstName User's first name
     * @param string $lastName User's last name
     * @param string $email User's email address
     * @param string $userName User's userName
     * @param string $password Plain text password to be hashed
     * @param UserRoleEnum $role User's role
     * @return User The newly created and persisted User entity
     */
    public function createUser(
        string $firstName,
        string $lastName,
        string $email,
        string $userName,
        string $password,
        UserRoleEnum $role
    ): User {
        // Pre-checks: collect *all* conflicts so we can return multiple field errors
        $details = [];
        if ($this->userRepository->findByEmail($email)) {
            $details['email'] = 'Este email ya está en uso.';
        }
        if ($this->userRepository->findOneBy(['userName' => $userName])) {
            $details['userName'] = 'Este nombre de usuario ya existe.';
        }
        if ($details) {
            // NOTE: do NOT add a 'message' key here
            throw new ValidationException($details);
        }

        $user = new User();
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setEmail($email);
        $user->setUserName($userName);
        $user->setRole($role);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        try {
            $this->em->persist($user);
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            // Source of truth says unique constraint(s) failed—recompute both
            $details = [];
            if ($this->userRepository->findByEmail($email)) {
                $details['email'] = 'Este email ya está en uso.';
            }
            if ($this->userRepository->findOneBy(['userName' => $userName])) {
                $details['userName'] = 'Este nombre de usuario ya existe.';
            }
            // If we cannot determine which one, you may put a generic field message,
            // but still don't add a 'message' key:
            if (!$details) {
                $details['email'] ??= 'Valor inválido.'; // safe generic
            }
            throw new ValidationException($details);
        }

        // ✅ Send Welcome Message (asincrónico por Messenger)
        $this->bus->dispatch(new WelcomeEmailMessage(
            userId: $user->getId()
        ));

        return $user;
    }

    /**
     * Apply a partial update. Only non-null DTO fields are applied.
     *
     * @param User $user
     * @param UpdateUserDto $dto
     */
    public function updateUser(User $user, UpdateUserDto $dto): User
    {

        if ($dto->firstName !== null) { $user->setFirstName($dto->firstName); }
        if ($dto->lastName  !== null) { $user->setLastName($dto->lastName);   }
        if ($dto->email     !== null) { $user->setEmail($dto->email);         }
        if ($dto->userName  !== null) { $user->setUserName($dto->userName);   }

        if ($dto->password !== null) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
        }

        $originalRole = $user->getRole();
        if ($dto->role instanceof UserRoleEnum && $dto->role !== $originalRole) {
            $this->applyRoleTransition($user, $originalRole, $dto->role);
            $user->setRole($dto->role);
        }

        $this->em->flush();
        return $user;
    }


    /**
     * Initiate email change: validates credentials, emits token to old email, payload carries newEmail.
     *
     * @throws \DomainException on conflicts or bad credentials
     * @throws ExceptionInterface
     */
    public function startEmailChange(User $user, string $newEmail, string $password): void
    {
        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            throw new \DomainException('bad_credentials');
        }
        if (!\filter_var($newEmail, \FILTER_VALIDATE_EMAIL)) {
            throw new \DomainException('invalid_email');
        }
        if ($newEmail === $user->getEmail()) {
            throw new \DomainException('same_email');
        }
        if ($this->userRepository->findByEmail($newEmail)) {
            throw new \DomainException('email_taken');
        }

        $oldEmail = $user->getEmail();

        $expiresAt = (new \DateTimeImmutable())->modify('+2 hours');
        $mint      = $this->tokens->mint(
            user: $user,
            type: AccountTokenType::EMAIL_CHANGE,
            expiresAt: $expiresAt,
            payload: ['newEmail' => $newEmail]
        );

        // Build link the user clicks (FRONTEND handles token and calls API)
        $frontend = rtrim($_ENV['FRONTEND_URL'] ?? 'http://localhost:4200', '/');
        // new route we'll add in Angular: /email/confirm
        $confirmUrl = $frontend . '/email/confirm?token=' . urlencode($mint['raw']);


        $this->bus->dispatch(new EmailChangeConfirmMessage(
            userId: $user->getId(),
            targetEmail: $newEmail,
            confirmUrl: $confirmUrl
        ));
        $this->bus->dispatch(new EmailChangeNotifyOldMessage(
            userId: $user->getId(),
            previousEmail: $oldEmail
        ));
    }

    /**
     * Consume email-change token and persist new email.
     *
     * @throws \DomainException when token invalid/expired or email now taken
     */
    public function confirmEmailChange(string $rawToken): User
    {
        $token = $this->tokens->consume($rawToken, AccountTokenType::EMAIL_CHANGE);
        $payload = $token->getPayload() ?? [];
        $newEmail = (string)($payload['newEmail'] ?? '');

        if ($newEmail === '' || $this->userRepository->findByEmail($newEmail)) {
            throw new \DomainException('email_taken_or_invalid');
        }

        $user = $token->getUser();
        $user->setEmail($newEmail);
        $this->em->flush();

        return $user;
    }

    /**
     * Change password for the authenticated user (requires current password).
     *
     * @throws \DomainException on invalid credentials
     */
    public function changeOwnPassword(User $user, ChangePasswordDto $dto): void
    {
        if (!$this->passwordHasher->isPasswordValid($user, (string)$dto->currentPassword)) {
            throw new \DomainException('bad_credentials');
        }
        if ($this->passwordHasher->isPasswordValid($user, (string)$dto->newPassword)) {
            throw new \DomainException('same_password');
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, (string)$dto->newPassword));
        $this->em->flush();

        $this->mailer->notifyPasswordChanged($user);
    }

    /**
     * Start a password-reset flow (generic response even if email not found).
     */
    public function startPasswordReset(ForgotPasswordDto $dto): void
    {
        $user = $this->userRepository->findByEmail((string)$dto->email);
        if (!$user) {
            // Do nothing; return generic 200 to avoid user enumeration.
            return;
        }

        $expiresAt = (new \DateTimeImmutable())->modify('+1 hour');
        $mint      = $this->tokens->mint($user, AccountTokenType::PASSWORD_RESET, $expiresAt);

        // Send user to the FRONTEND reset page, which will POST the token + new password to the API.
        // Prefer FRONTEND_URL, fallback to a sensible local dev URL.
        $frontendBase = rtrim($_ENV['FRONTEND_URL'] ?? $_ENV['APP_FRONTEND_URL'] ?? 'http://localhost:4200', '/');
        $resetUrl     = sprintf('%s/password/reset?token=%s', $frontendBase, urlencode($mint['raw']));

        $this->mailer->sendPasswordResetLink($user, $resetUrl);
    }

    /**
     * Confirm password reset using a token and new password.
     *
     * @throws \DomainException when token invalid/expired
     */
    public function confirmPasswordReset(ResetPasswordDto $dto): void
    {
        $token = $this->tokens->consume((string)$dto->token, AccountTokenType::PASSWORD_RESET);
        $user  = $token->getUser();

        $user->setPassword($this->passwordHasher->hashPassword($user, (string)$dto->newPassword));
        $this->em->flush();

        $this->mailer->notifyPasswordChanged($user);
    }

    /**
     * Deletes a user from the database.
     *
     * @param User $user The user to delete
     */
    public function removeUser(User $user): void
    {
        $this->em->remove($user);
        $this->em->flush();
    }
}
