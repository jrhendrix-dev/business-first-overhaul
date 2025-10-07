<?php
// src/Dto/Grade/GradeViewDto.php
declare(strict_types=1);

namespace App\Dto\Grade;

use App\Enum\GradeComponentEnum;

/**
 * Read model for grade listings and detail views.
 *
 * @phpstan-type ClassroomMini array{id:int, name:string}
 * @phpstan-type StudentMini array{id:int, firstName:string, lastName:string, email:string}
 */
final class GradeViewDto
{
    /**
     * @param ClassroomMini $classroom
     * @param StudentMini|null $student
     */
    public function __construct(
        public int $id,
        public string $componentLabel,
        public float $score,
        public float $maxScore,
        public float $percent,
        public string $gradedAt,
        public int $enrollmentId,
        public array $classroom,
        public ?array $student,
    ) {
    }
}
