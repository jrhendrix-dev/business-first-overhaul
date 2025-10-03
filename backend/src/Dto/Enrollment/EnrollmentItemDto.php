<?php
// src/Dto/Enrollment/EnrollmentItemDto.php
declare(strict_types=1);

namespace App\Dto\Enrollment;

/**
 * Lightweight read model for enrollment listings.
 *
 * @phpstan-type StudentMini array{id:int, firstName:string, lastName:string, email:string}
 * @phpstan-type EnrollmentItem array{
 *   id:int,
 *   classId:int,
 *   status:string,
 *   enrolledAt: ?string,
 *   droppedAt: ?string,
 *   student: StudentMini
 * }
 */
final class EnrollmentItemDto
{
    public function __construct(
        public int $id,
        public int $classId,
        public string $status,
        public ?string $enrolledAt,
        public ?string $droppedAt,
        /** @var array{id:int, firstName:string, lastName:string, email:string} */
        public array $student,
    ) {}
}
