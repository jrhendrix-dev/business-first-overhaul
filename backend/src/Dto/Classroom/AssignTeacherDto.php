<?php
// src/Dto/Classroom/AssignTeacherDto.php
declare(strict_types=1);

namespace App\Dto\Classroom;

use Symfony\Component\Validator\Constraints as Assert;

/** Payload for assigning a teacher to a classrooms. */
final class AssignTeacherDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $teacherId = 0,
    ) {}

    /** @param array<string,mixed> $a */
    public static function fromArray(array $a): self
    {
        return new self(teacherId: (int) ($a['teacherId'] ?? 0));
    }
}

