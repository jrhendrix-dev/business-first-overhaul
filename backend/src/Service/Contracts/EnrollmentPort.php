<?php
declare(strict_types=1);

namespace App\Service\Contracts;

use App\Entity\Classroom;
use App\Entity\Enrollment;
use App\Entity\User;

/**
 * Port defining the enrollment operations used by the application layer.
 *
 * Services (e.g. GradeManager, Controllers) should depend on this interface
 * instead of a concrete EnrollmentManager to keep the code testable and decoupled.
 */
interface EnrollmentPort
{
    /**
     * Ensure the student is ACTIVE in the classroom (idempotent).
     *
     * @param User $student
     * @param Classroom $classroom
     * @return Enrollment ACTIVE enrollment row after the operation.
     */
    public function enroll(User $student, Classroom $classroom): Enrollment;

    /**
     * Soft-drop the ACTIVE enrollment for the student (optionally limited to one classroom).
     *
     * @param User $student
     * @param Classroom|null $classroom If null, drop any active enrollment for the student.
     */
    public function dropActiveForStudent(User $student, ?Classroom $classroom = null): void;

    /**
     * Soft-drop all ACTIVE enrollments for a student.
     */
    public function dropAllActiveForStudent(User $student): void;

    /**
     * Soft-drop all ACTIVE enrollments in a classroom.
     */
    public function dropAllActiveForClassroom(Classroom $classroom): void;

    /**
     * Fetch the Enrollment for a (studentId, classId) pair or throw if none/invalid.
     *
     * Used by GradeManager and controllers to resolve the target enrollment
     * without duplicating repository logic.
     *
     * @param int $studentId
     * @param int $classId
     * @return Enrollment
     * @throws \RuntimeException when the enrollment does not exist or is not accessible.
     */
    public function getByIdsOrFail(int $studentId, int $classId): Enrollment;
}
