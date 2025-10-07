<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Classroom;
use App\Enum\EnrollmentStatusEnum;
use App\Enum\UserRoleEnum;
use App\Entity\Enrollment;
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
     * Return the student if they have an ACTIVE enrollment in the given classroom.
     *
     * @param int $studentId
     * @param int $classroomId
     * @return User|null
     */
    public function findStudentInClassroom(int $studentId, int $classroomId): ?User
    {
        $qb = $this->createQueryBuilder('u')
            // join through Enrollment; e.student is the owning side to User
            ->innerJoin(Enrollment::class, 'e', 'WITH', 'e.student = u')
            ->andWhere('u.id = :studentId')
            // compare by FK id, not object
            ->andWhere('IDENTITY(e.classroom) = :classroomId')
            ->andWhere('e.status = :active')
            ->setMaxResults(1)
            ->setParameter('studentId', $studentId)
            ->setParameter('classroomId', $classroomId)
            ->setParameter('active', EnrollmentStatusEnum::ACTIVE);

        return $qb->getQuery()->getOneOrNullResult();
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
     * Students who are NOT actively enrolled in any classroom.
     *
     * @return User[]
     */
    public function findStudentsWithoutClassroom(): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select('DISTINCT u')
            ->andWhere('u.role = :role')
            // left join ACTIVE enrollments; if none, e.id will be NULL
            ->leftJoin(Enrollment::class, 'e', 'WITH', 'e.student = u AND e.status = :active')
            ->andWhere('e.id IS NULL')
            ->setParameter('role', UserRoleEnum::STUDENT)
            ->setParameter('active', EnrollmentStatusEnum::ACTIVE);

        return $qb->getQuery()->getResult();
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
            ->andWhere('LOWER(u.firstName) LIKE :name OR LOWER(u.lastName) LIKE :name')
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

    /** @return User[] */
    public function findUnassignedStudents(): array
    {
        $roleParam = UserRoleEnum::STUDENT instanceof \BackedEnum
            ? UserRoleEnum::STUDENT->value
            : UserRoleEnum::STUDENT;

        $activeParam = EnrollmentStatusEnum::ACTIVE instanceof \BackedEnum
            ? EnrollmentStatusEnum::ACTIVE->value
            : EnrollmentStatusEnum::ACTIVE;

        return $this->createQueryBuilder('u')
            ->leftJoin('App\Entity\Enrollment', 'e', 'WITH', 'e.student = u AND e.status = :active')
            ->andWhere('u.role = :role')
            ->andWhere('e.id IS NULL')
            ->setParameter('role', $roleParam)
            ->setParameter('active', $activeParam)
            ->getQuery()
            ->getResult();
    }

    /** @return User[] */
    public function findUnassignedTeachers(): array
    {
        // If you're using Doctrine enum type, you can pass the enum itself.
        $roleParam = UserRoleEnum::TEACHER instanceof \BackedEnum
            ? UserRoleEnum::TEACHER->value
            : UserRoleEnum::TEACHER;

        return $this->createQueryBuilder('u')
            ->leftJoin('App\Entity\Classroom', 'c', 'WITH', 'c.teacher = u')
            ->andWhere('u.role = :role')
            ->andWhere('c.id IS NULL')
            ->setParameter('role', $roleParam)
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
