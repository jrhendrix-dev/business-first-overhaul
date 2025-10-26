<?php

namespace App\Service;

use App\Entity\Classroom;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Enum\EnrollmentStatusEnum;
use App\Repository\EnrollmentRepository;
use App\Service\Contracts\EnrollmentPort;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

/**
 * Coordinates enrollment use-cases while delegating reads to EnrollmentRepository.
 *
 * Design goals:
 * - Idempotent "enroll" (PUT semantics).
 * - Never violate the (student_id, classroom_id) unique index.
 * - Prefer repository methods over ad-hoc queries.
 */
final class EnrollmentManager implements EnrollmentPort
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EnrollmentRepository $enrollments,
    ) {}

    /**
     * Ensure the student is ACTIVE in the classrooms (idempotent).
     *
     * Flow:
     *  - If ACTIVE exists -> return it (no DB writes).
     *  - Else if any enrollment for this pair exists (e.g., DROPPED) -> reactivate the same row.
     *  - Else -> create a new ACTIVE enrollment.
     *
     * @param Classroom $classroom
     * @param User      $student
     * @return Enrollment ACTIVE enrollment row after the operation.
     */
    public function enrollByIds(Classroom $classroom, User $student): Enrollment
    {
        // 1) Already ACTIVE?
        if ($active = $this->enrollments->findActiveOneByStudentAndClassroom($student, $classroom)) {
            return $active;
        }

        // 2) Reactivate existing historical row (avoids unique index violation)
        if ($existing = $this->enrollments->findAnyOneByStudentAndClassroom($student, $classroom)) {
            $existing->setStatus(EnrollmentStatusEnum::ACTIVE);
            $existing->setDroppedAt(null);
            // Optional: update enrolledAt if you want the reactivation date
            // $existing->setEnrolledAt(new \DateTimeImmutable());
            $this->em->flush();

            return $existing;
        }

        // 3) Create fresh row
        $enrollment = new Enrollment();
        $enrollment
            ->setClassroom($classroom)
            ->setStudent($student)
            ->setStatus(EnrollmentStatusEnum::ACTIVE)
            ->setEnrolledAt(new \DateTimeImmutable());

        $this->em->persist($enrollment);
        $this->em->flush();

        return $enrollment;
    }



    /**
     * Soft drop the ACTIVE enrollment, if present. No-op if not currently active.
     *
     * @param Classroom $classroom
     * @param User      $student
     */
    public function softDropByIds(Classroom $classroom, User $student): void
    {
        $active = $this->enrollments->findActiveOneByStudentAndClassroom($student, $classroom);
        if (!$active) {
            return;
        }

        $active
            ->setStatus(EnrollmentStatusEnum::DROPPED)
            ->setDroppedAt(new \DateTimeImmutable());

        // Keep classrooms link for history; do NOT null it out.
        $this->em->flush();
    }

    /**
     * Shortcut for “is currently enrolled (ACTIVE)”.
     *
     * @param Classroom $classroom
     * @param User      $student
     * @return bool
     */
    public function isEnrolled(Classroom $classroom, User $student): bool
    {
        // Delegates to repo method you already have.
        return $this->enrollments->isEnrolled($student, $classroom);
    }

    /**
     * @return Enrollment[] ACTIVE enrollments for the class ordered by enrolledAt ASC.
     */
    public function getActiveEnrollmentsForClassroom(Classroom $classroom): array
    {
        return $this->enrollments->findActiveByClassroom($classroom);
    }

    public function getAnyEnrollmentForClassroom(Classroom $classroom): array
    {
        return $this->enrollments->findAnyByClassroom($classroom);
    }


    /** Port implementation: bulk drop all ACTIVE enrollments for the classrooms */
    public function dropAllActiveForClassroom(Classroom $classroom): void
    {
        $this->enrollments->softDropAllActiveByClassroom($classroom);
    }


    public function enroll(User $student, Classroom $classroom): Enrollment
    {
        return $this->enrollByIds($classroom, $student);
    }

    public function dropActiveForStudent(User $student, ?Classroom $classroom = null): void
    {
        if ($classroom === null) {
            $this->dropAllActiveForStudent($student);
            return;
        }
        $this->softDropByIds($classroom, $student);
    }

    public function dropAllActiveForStudent(User $student): void
    {
        $this->enrollments->softDropAllActiveByStudent($student);
    }

    public function getEnrollmentById(int $enrollmentId): Enrollment
    {
        return $this->enrollments->findEnrollmentById($enrollmentId);
    }


    public function getActiveForStudent(User $student): array
    {
        return $this->enrollments->findActiveByStudent($student);
    }


    /**
     * Fetch the Enrollment for a given (studentId, classId) pair or throw if none found.
     *
     * @param int $studentId
     * @param int $classId
     * @return Enrollment
     *
     * @throws RuntimeException if no matching enrollment is found.
     */
    public function getByIdsOrFail(int $studentId, int $classId): Enrollment
    {
        $enrollment = $this->enrollments->findOneBy([
            'student'   => $studentId,
            'classrooms' => $classId,
        ]);

        if (!$enrollment) {
            throw new RuntimeException(sprintf(
                'Enrollment not found for studentId=%d and classId=%d',
                $studentId,
                $classId
            ));
        }

        return $enrollment;
    }

    public function countActiveByClassroom(Classroom $classroom): int
    {
        return $this->enrollments->countActiveByClassroom($classroom);
    }
}
