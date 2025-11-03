<?php
declare(strict_types=1);

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
 * - Use repository QBs for SELECTs.
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
     */
    public function isEnrolled(User $student, Classroom $classroom): bool
    {
        return (bool) $this->createQueryBuilder('e')
            ->select('1')
            ->andWhere('e.student = :s')->setParameter('s', $student)
            ->andWhere('e.classroom = :c')->setParameter('c', $classroom)   // singular
            ->andWhere('e.status = :st')->setParameter('st', EnrollmentStatusEnum::ACTIVE)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find an Enrollment by its primary key.
     */
    public function findEnrollmentById(int $id): ?Enrollment
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.id = :id')->setParameter('id', $id)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * Get all ACTIVE enrollments for a classroom (ordered oldest first).
     *
     * @return Enrollment[]
     */
    public function findActiveByClassroom(Classroom $classroom): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.student', 's')->addSelect('s')
            ->andWhere('e.classroom = :c')->setParameter('c', $classroom)   // singular
            ->andWhere('e.status = :st')->setParameter('st', EnrollmentStatusEnum::ACTIVE)
            ->orderBy('e.enrolledAt', 'ASC')
            ->getQuery()->getResult();
    }

    /**
     * Return every enrollment (any status) for a classroom id.
     *
     * @return Enrollment[]
     */
    public function findAllByClassroomId(int $classroomId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.classroom = :cid')->setParameter('cid', $classroomId)
            ->addOrderBy('e.enrolledAt', 'DESC')
            ->getQuery()->getResult();
    }

    /**
     * Return ACTIVE enrollments for a classroom id.
     *
     * @return Enrollment[]
     */
    public function findActiveByClassroomId(int $classroomId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.classroom = :cid')->setParameter('cid', $classroomId)
            ->andWhere('e.status = :status')->setParameter('status', EnrollmentStatusEnum::ACTIVE)
            ->addOrderBy('e.enrolledAt', 'DESC')
            ->getQuery()->getResult();
    }

    /**
     * Get all enrollments (any status) for a classroom (ordered oldest first).
     *
     * @return Enrollment[]
     */
    public function findAnyByClassroom(Classroom $classroom): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.student', 's')->addSelect('s')
            ->leftJoin('e.classroom', 'c')->addSelect('c')                  // singular
            ->andWhere('e.classroom = :c')->setParameter('c', $classroom)   // singular
            ->orderBy('e.enrolledAt', 'ASC')
            ->getQuery()->getResult();
    }

    /**
     * Find any enrollment by scalar ids (useful for controller paths).
     */
    public function findOneByStudentIdAndClassId(int $studentId, int $classId): ?Enrollment
    {
        return $this->createQueryBuilder('e')
            ->andWhere('IDENTITY(e.student) = :sid')->setParameter('sid', $studentId)
            ->andWhere('IDENTITY(e.classroom) = :cid')->setParameter('cid', $classId) // singular
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * Find the ACTIVE enrollment for a given student/classroom pair.
     */
    public function findActiveOneByStudentAndClassroom(User $student, Classroom $classroom): ?Enrollment
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.student = :student')->setParameter('student', $student)
            ->andWhere('e.classroom = :classroom')->setParameter('classroom', $classroom) // singular
            ->andWhere('e.status = :st')->setParameter('st', EnrollmentStatusEnum::ACTIVE)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * Find the most recent enrollment (any status) for a student/classroom pair.
     */
    public function findAnyOneByStudentAndClassroom(User $student, Classroom $classroom): ?Enrollment
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.student = :student')->setParameter('student', $student)
            ->andWhere('e.classroom = :classroom')->setParameter('classroom', $classroom) // singular
            ->orderBy('e.enrolledAt', 'DESC')
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * Eager-load enrollments by classroom id (joins student & classroom).
     *
     * @return Enrollment[]
     */
    public function findByClassroomId(int $classroomId): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.student', 's')->addSelect('s')
            ->leftJoin('e.classroom', 'c')->addSelect('c')                  // singular
            ->andWhere('c.id = :cid')->setParameter('cid', $classroomId)
            ->getQuery()->getResult();
    }

    /**
     * Soft-drop **all ACTIVE** enrollments in the given classroom via bulk DQL.
     *
     * @return int Number of affected rows.
     */
    public function softDropAllActiveByClassroom(Classroom $classroom): int
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->update(Enrollment::class, 'e')
            ->set('e.status', ':dropped')
            ->set('e.droppedAt', ':now')
            ->where('e.classroom = :class')                                 // singular
            ->andWhere('e.status = :active')
            ->setParameter('class', $classroom)
            ->setParameter('active', EnrollmentStatusEnum::ACTIVE)
            ->setParameter('dropped', EnrollmentStatusEnum::DROPPED)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()->execute();
    }

    /**
     * Soft-drop **all ACTIVE** enrollments for a given student across classrooms.
     *
     * @return int Number of affected rows.
     */
    public function softDropAllActiveByStudent(User $student): int
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->update(Enrollment::class, 'e')
            ->set('e.status', ':dropped')
            ->set('e.droppedAt', ':now')
            ->where('e.student = :student')
            ->andWhere('e.status = :active')
            ->setParameter('student', $student)
            ->setParameter('active', EnrollmentStatusEnum::ACTIVE)
            ->setParameter('dropped', EnrollmentStatusEnum::DROPPED)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()->execute();
    }

    /**
     * @return Enrollment[] ACTIVE enrollments for the student with classroom+teacher preloaded.
     */
    public function findActiveByStudent(User $student): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.classroom', 'c')->addSelect('c')                   // singular
            ->leftJoin('c.teacher', 't')->addSelect('t')
            ->andWhere('e.student = :s')->setParameter('s', $student)
            ->andWhere('e.status = :st')->setParameter('st', EnrollmentStatusEnum::ACTIVE)
            ->orderBy('c.name', 'ASC')
            ->getQuery()->getResult();
    }

    /**
     * Count ACTIVE enrollments in a classroom.
     */
    public function countActiveByClassroom(Classroom $classroom): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.classroom = :c')->setParameter('c', $classroom)    // singular
            ->andWhere('e.status = :st')->setParameter('st', EnrollmentStatusEnum::ACTIVE)
            ->getQuery()->getSingleScalarResult();
    }

    /**
     * Get all DROPPED enrollments for a classroom.
     *
     * @param Classroom $classroom
     * @return Enrollment[]
     */
    public function findDroppedByClassroom(Classroom $classroom): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.classroom = :c')
            ->andWhere('e.status = :st')
            ->setParameter('c', $classroom)
            ->setParameter('st', EnrollmentStatusEnum::DROPPED)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count DROPPED enrollments for a classroom.
     */
    public function countDroppedByClassroom(Classroom $classroom): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.classroom = :c')
            ->andWhere('e.status = :st')
            ->setParameter('c', $classroom)
            ->setParameter('st', EnrollmentStatusEnum::DROPPED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findDroppedByClassroomLimited(Classroom $classroom, ?\DateTimeImmutable $notOlderThan = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.classroom = :c')
            ->andWhere('e.status = :st')
            ->setParameter('c', $classroom)
            ->setParameter('st', EnrollmentStatusEnum::DROPPED);

        if ($notOlderThan) {
            $qb->andWhere('e.droppedAt IS NOT NULL AND e.droppedAt >= :since')
                ->setParameter('since', $notOlderThan);
        }

        return $qb->getQuery()->getResult();
    }

    /** Hard delete a DROPPED enrollment (guard in manager). */
    public function remove(Enrollment $enrollment): void
    {
        $this->_em->remove($enrollment);
    }

    /** Bulk purge DROPPED older than a cutoff */
    public function purgeDroppedOlderThan(\DateTimeImmutable $before): int
    {
        return (int)$this->createQueryBuilder('e')
            ->delete()
            ->andWhere('e.status = :st')
            ->andWhere('e.droppedAt IS NOT NULL AND e.droppedAt < :before')
            ->setParameter('st', EnrollmentStatusEnum::DROPPED)
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();
    }

    /**
     * Permanently delete a DROPPED enrollment by id.
     * Returns number of rows affected (0 or 1).
     */
    public function hardDeleteIfDropped(int $id): int
    {
        // If you use an enum, prefer parameterizing by its value:
        $dropped = \defined(EnrollmentStatusEnum::class)
            ? EnrollmentStatusEnum::DROPPED->value
            : 'DROPPED';

        return $this->createQueryBuilder('e')
            ->delete()
            ->where('e.id = :id')
            ->andWhere('e.status = :status')
            ->setParameter('id', $id)
            ->setParameter('status', $dropped)
            ->getQuery()
            ->execute();
    }
}
