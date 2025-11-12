<?php
// src/Service/Contracts/EnrollmentPort.php
declare(strict_types=1);

namespace App\Service\Contracts;

use App\Entity\Classroom;
use App\Entity\Enrollment;
use App\Entity\User;

/**
 * Boundary for enrollment use-cases (query + command).
 * Controllers and other application services should depend on this interface.
 */
interface EnrollmentPort
{
    /**
     * Enroll (or reactivate) a student in a classrooms (idempotent).
     *
     * @param Classroom $classroom
     * @param User $student
     * @return Enrollment
     */
    public function enrollByIds(Classroom $classroom, User $student): Enrollment;

    /**
     * Soft-drop a student from a classrooms (no-op if already inactive).
     *
     * @param Classroom $classroom
     * @param User $student
     */
    public function softDropByIds(Classroom $classroom, User $student): void;

    /**
     * Soft drop the ACTIVE enrollment for student in a specific classrooms (or all if classrooms is null).
     */
    public function dropActiveForStudent(User $student, ?Classroom $classroom = null): void;

    /**
     * Bulk soft-drop all ACTIVE enrollments for a classrooms.
     *
     * @param Classroom $classroom
     */
    public function dropAllActiveForClassroom(Classroom $classroom): void;

    /**
     * Soft drop all ACTIVE enrollments for student.
     */
    public function dropAllActiveForStudent(User $student): void;

    /**
     * Is student currently ACTIVE in classrooms?
     */
    public function isEnrolled(Classroom $classroom, User $student): bool;

    /**
     * ACTIVE enrollments for a classrooms (ordered by enrolledAt ASC).
     *
     * @return Enrollment[]
     */
    public function getActiveEnrollmentsForClassroom(Classroom $classroom): array;

    /**
     * Idempotent enrollment (no duplicate active rows).
     */
    public function enroll(User $student, Classroom $classroom): Enrollment;


    /**
     * Any enrollments for a classrooms (any status).
     *
     * @return Enrollment[]
     */
    public function getAnyEnrollmentForClassroom(Classroom $classroom): array;

    /**
     * ACTIVE enrollments for a student across classrooms.
     *
     * @return Enrollment[]
     */
    public function getActiveForStudent(User $student): array;

    /**
     * Count ACTIVE enrollments for a classrooms.
     *
     * @param Classroom $classroom
     * @return int
     */
    public function countActiveByClassroom(Classroom $classroom): int;

    /**
     * Resolve the enrollment for a student/class pair or throw if missing.
     *
     * Implementations should surface a domain-level exception when the
     * enrollment cannot be located so callers (e.g. GradeManager) can
     * translate the failure into a user-facing error.
     */
    public function getByIdsOrFail(int $studentId, int $classId): Enrollment;
}
