<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Classroom;
use App\Enum\UserRoleEnum;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Repository class for User entity.
 * Provides database query methods for user management operations.
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Finds a student user by ID and verifies they belong to a specific classroom.
     *
     * @param int $studentId The ID of the student to find
     * @param int $classroomId The ID of the classroom to check
     * @return User|null The student entity if found in the classroom, null otherwise
     * @throws ORMException If query execution fails
     */
    public function findStudentInClassroom(int $studentId, int $classroomId): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.id = :studentId')
            ->andWhere('u.classroom = :classroom')
            ->setParameter('studentId', $studentId)
            ->setParameter(
                'classroom',
                $this->getEntityManager()->getReference(Classroom::class, $classroomId)
            )
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Finds a user by their unique ID.
     *
     * @param int $id The ID of the user to find
     * @return User|null The user entity if found, null otherwise
     */
    public function findUserById(int $id): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Finds a user by their email address.
     *
     * @param string $email The email address of the user to find
     * @return User|null The user entity if found, null otherwise
     */
    public function findByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retrieves all student users.
     *
     * @return User[] Array of student user entities
     */
    public function findAllStudents(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.role = :role')
            ->setParameter('role', UserRoleEnum::STUDENT)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retrieves all teacher users.
     *
     * @return User[] Array of teacher user entities
     */
    public function findAllTeachers(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.role = :role')
            ->setParameter('role', UserRoleEnum::TEACHER)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retrieves all student users without a classroom assignment.
     *
     * @return User[] Array of student user entities without classroom
     */
    public function findStudentsWithoutClassroom(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.role = :role')
            ->andWhere('u.classroom IS NULL')
            ->setParameter('role', UserRoleEnum::STUDENT)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retrieves all teacher users without a classroom assignment.
     * Uses a LEFT JOIN to identify teachers not associated with any classroom.
     *
     * @return User[] Array of teacher user entities without classroom
     */
    public function findTeachersWithoutClassroom(): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin(Classroom::class, 'c', 'WITH', 'c.teacher = u')
            ->andWhere('u.role = :role')
            ->andWhere('c.id IS NULL')
            ->setParameter('role', UserRoleEnum::TEACHER)
            ->getQuery()
            ->getResult();
    }

    /**
     * Searches for users by name with an optional role filter.
     * Performs case-insensitive search on first and last names.
     *
     * @param string $name The name to search for (case-insensitive)
     * @param UserRoleEnum|null $role Optional role filter for the search
     * @return User[] Array of user entities matching the search criteria
     */
    public function searchByName(string $name, ?UserRoleEnum $role = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('LOWER(u.firstname) LIKE :name OR LOWER(u.lastname) LIKE :name')
            ->setParameter('name', '%' . strtolower($name) . '%');

        if ($role !== null) {
            $qb->andWhere('u.role = :role')
                ->setParameter('role', $role);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Counts the number of users with a specific role.
     *
     * @param UserRoleEnum $role The role to count
     * @return int Number of users with the specified role
     */
    public function countByRole(UserRoleEnum $role): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.role = :role')
            ->setParameter('role', $role)
            ->getQuery()
            ->getSingleScalarResult();  //Want a single number
    }

    /**
     * Retrieves users registered within the last specified number of days.
     * Results are ordered by registration date in descending order.
     *
     * @param int $days Number of days to look back (default: 30)
     * @return User[] Array of recently registered user entities
     */
    public function findRecentlyRegisteredUsers(int $days = 30): array
    {
        $date = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('u')
            ->andWhere('u.createdAt >= :date')
            ->setParameter('date', $date)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Unassigns all users from a specific classroom in a bulk operation.
     * This is a direct DQL update that skips entity lifecycle events.
     *
     * @param Classroom $classroom The classroom to unassign users from
     * @return int Number of affected rows (users unassigned)
     * @note This is a bulk operation and does not trigger entity events or flush the entity manager
     */
    public function unassignAllFromClassroom(Classroom $classroom): int
    {
        // DQL bulk update (skips lifecycle events)
        $q = $this->getEntityManager()->createQuery(
            'UPDATE App\Entity\User u SET u.classroom = NULL WHERE u.classroom = :classroom'
        )->setParameter('classroom', $classroom);

        return $q->execute(); // affected rows
    }
}
