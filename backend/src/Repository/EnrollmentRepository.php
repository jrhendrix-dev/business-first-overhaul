<?php

namespace App\Repository;

use App\Entity\Classroom;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Enum\EnrollmentStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Enrollment>
 *
 * Repository for Enrollment read/modify queries.
 *
 * Notes:
 * - Prefer repository QBs for SELECTS.
 * - For bulk UPDATEs use the EntityManager's QueryBuilder (bypasses UoW).
 */
final class EnrollmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enrollment::class);
    }

    /**
     * Check whether a student is currently ACTIVE in a classroom.
     *
     * @param User      $student
     * @param Classroom $classroom
     * @return bool True if there is at least one ACTIVE enrollment.
     */
    public function isEnrolled(User $student, Classroom $classroom): bool
    {
        return (bool) $this->createQueryBuilder('e')
            ->select('1')
            ->andWhere('e.student = :s')->setParameter('s', $student)
            ->andWhere('e.classroom = :c')->setParameter('c', $classroom)
            ->andWhere('e.status = :st')->setParameter('st', EnrollmentStatusEnum::ACTIVE)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find an Enrollment by its primary key.
     *
     * @param int $id
     * @return Enrollment|null
     */
    public function findEnrollmentById(int $id): ?Enrollment
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.id = :id')->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get all ACTIVE enrollments for a classroom (ordered oldest first).
     *
     * @param Classroom $classroom
     * @return Enrollment[]
     */
    public function findActiveByClassroom(Classroom $classroom): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.student', 's')->addSelect('s')
            ->leftJoin('e.classroom', 'c')->addSelect('c')
            ->andWhere('e.classroom = :c')->setParameter('c', $classroom)
            ->andWhere('e.status = :st')->setParameter('st', EnrollmentStatusEnum::ACTIVE)
            ->orderBy('e.enrolledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all enrollments (any status) for a classroom (ordered oldest first).
     *
     * @param Classroom $classroom
     * @return Enrollment[]
     */

    public function findAnyByClassroom(Classroom $classroom): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.student', 's')->addSelect('s')
            ->leftJoin('e.classroom', 'c')->addSelect('c')
            ->andWhere('e.classroom = :c')->setParameter('c', $classroom)
            ->orderBy('e.enrolledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find any enrollment by scalar ids (useful for controller paths).
     *
     * @param int $studentId
     * @param int $classId
     * @return Enrollment|null
     */
    public function findOneByStudentIdAndClassId(int $studentId, int $classId): ?Enrollment
    {
        return $this->createQueryBuilder('e')
            ->andWhere('IDENTITY(e.student) = :sid')->setParameter('sid', $studentId)
            ->andWhere('IDENTITY(e.classroom) = :cid')->setParameter('cid', $classId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find the ACTIVE enrollment for a given student/classroom pair.
     *
     * @param User      $student
     * @param Classroom $classroom
     * @return Enrollment|null
     */
    public function findActiveOneByStudentAndClassroom(User $student, Classroom $classroom): ?Enrollment
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.student = :student')->setParameter('student', $student)
            ->andWhere('e.classroom = :classroom')->setParameter('classroom', $classroom)
            ->andWhere('e.status = :st')->setParameter('st', EnrollmentStatusEnum::ACTIVE)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find the most recent enrollment (any status) for a student/classroom pair.
     *
     * @param User      $student
     * @param Classroom $classroom
     * @return Enrollment|null
     */
    public function findAnyOneByStudentAndClassroom(User $student, Classroom $classroom): ?Enrollment
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.student = :student')->setParameter('student', $student)
            ->andWhere('e.classroom = :classroom')->setParameter('classroom', $classroom)
            ->orderBy('e.enrolledAt', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Eager-load enrollments by classroom id (joins student & classroom).
     *
     * @param int $classroomId
     * @return Enrollment[]
     */
    public function findByClassroomId(int $classroomId): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.student', 's')->addSelect('s')
            ->leftJoin('e.classroom', 'c')->addSelect('c')
            ->andWhere('c.id = :cid')->setParameter('cid', $classroomId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Soft-drop **all ACTIVE** enrollments in the given classroom via bulk DQL.
     *
     * ⚠️ This bypasses the UnitOfWork; refresh entities you already hold.
     *
     * @param Classroom $classroom
     * @return int Number of affected rows.
     */
    public function softDropAllActiveByClassroom(Classroom $classroom): int
    {
        $now = new \DateTimeImmutable();

        return $this->getEntityManager()->createQueryBuilder()
            ->update(Enrollment::class, 'e')
            ->set('e.status', ':dropped')
            ->set('e.droppedAt', ':now')
            ->where('e.classroom = :class')
            ->andWhere('e.status = :active')
            ->setParameter('class', $classroom)
            ->setParameter('active', EnrollmentStatusEnum::ACTIVE)
            ->setParameter('dropped', EnrollmentStatusEnum::DROPPED)
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();
    }

    /**
     * Soft-drop **all ACTIVE** enrollments for a given student across classrooms.
     *
     * ⚠️ Bypasses UnitOfWork; refresh entities you already hold.
     *
     * @param User $student
     * @return int Number of affected rows.
     */
    public function softDropAllActiveByStudent(User $student): int
    {
        $now = new \DateTimeImmutable();

        return $this->getEntityManager()->createQueryBuilder()
            ->update(Enrollment::class, 'e')
            ->set('e.status', ':dropped')
            ->set('e.droppedAt', ':now')
            ->where('e.student = :student')
            ->andWhere('e.status = :active')
            ->setParameter('student', $student)
            ->setParameter('active', EnrollmentStatusEnum::ACTIVE)
            ->setParameter('dropped', EnrollmentStatusEnum::DROPPED)
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();
    }
}
