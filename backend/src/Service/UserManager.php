<?php

namespace App\Service;

use App\Entity\Classroom;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

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
     * Changes a user's role and updates their classroom assignment if needed.
     * If changing from STUDENT to another role, removes classroom association.
     *
     * @param User $user The user to modify
     * @param UserRoleEnum $role The new role to assign
     */
    public function changeRole(User $user, UserRoleEnum $role): void
    {
        if ($user->getRole() === $role) {
            return;
        }

        if ($user->getRole() === UserRoleEnum::STUDENT) {
            $user->setClassroom(null);
        }

        $user->setRole($role);
        $this->em->flush();
    }

    /**
     * Updates a user's password with a new hashed value.
     *
     * @param User $user The user to modify
     * @param string $password The plain text password to hash and store
     */
    public function changePassword(User $user, string $password): void
    {
        $user->setPassword($password);
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
     * Verifies if a student belongs to a specific classroom.
     *
     * @param int $studentId The student's ID
     * @param int $classroomId The classroom's ID
     * @return User|null The student entity if found in the classroom, null otherwise
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
     * Retrieves students not assigned to any classroom.
     *
     * @return User[] Array of student User entities without classroom assignments
     */
    public function getStudentsWithoutClassroom(): ?array
    {
        return $this->userRepository->findStudentsWithoutClassroom();
    }

    /**
     * Retrieves teachers not assigned to any classroom.
     *
     * @return User[] Array of teacher User entities without classroom assignments
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
     * Unassigns all students from a classroom and clears the entity manager cache.
     *
     * @param Classroom $classroom The classroom to unassign students from
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
     * @param string $username User's username
     * @param string $password Plain text password to be hashed
     * @param UserRoleEnum $role User's role
     * @return User The newly created and persisted User entity
     */
    public function createUser(
        string $firstName,
        string $lastName,
        string $email,
        string $username,
        string $password,
        UserRoleEnum $role
    ): User {
        // Optional soft checks (good UX, not race-safe)
        if ($this->userRepository->findByEmail($email)) {
            throw new \DomainException('email_taken');
        }
        if ($this->userRepository->findOneBy(['username' => $username])) {
            throw new \DomainException('username_taken');
        }

        $user = new User();
        $user->setFirstname($firstName);
        $user->setLastname($lastName);
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setRole($role);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        try {
            $this->em->persist($user);
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            // The DB is the source of truth; map constraint to a friendly domain error.
            // If you have named constraints, you can inspect $e to distinguish which one.
            // For now, we check which one exists to return a stable message:
            if ($this->userRepository->findByEmail($email)) {
                throw new \DomainException('email_taken');
            }
            throw new \DomainException('username_taken');
        }

        return $user;
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
