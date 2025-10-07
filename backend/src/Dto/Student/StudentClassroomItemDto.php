<?php
// src/Dto/Student/StudentClassroomItemDto.php
declare(strict_types=1);

namespace App\Dto\Student;

/**
 * @phpstan-type TeacherMini array{id:int, firstName:string, lastName:string, email:string}|null
 */
final class StudentClassroomItemDto
{
    public function __construct(
        /** Classroom id */
        public int $classId,
        /** Classroom name */
        public string $className,
        /** Enrollment status, e.g. ACTIVE / DROPPED / COMPLETED */
        public string $status,
        /** ISO8601 (or null) */
        public ?string $enrolledAt,
        /** ISO8601 (or null) */
        public ?string $droppedAt,
        /** Minimal teacher projection (or null if unassigned) */
        public ?array $teacher
    ) {}
}
