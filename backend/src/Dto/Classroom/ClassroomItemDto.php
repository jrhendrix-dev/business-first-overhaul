<?php
// src/Dto/Classroom/ClassroomItemDto.php
declare(strict_types=1);

namespace App\Dto\Classroom;

/**
 * Summary model for classrooms listings.
 *
 * @phpstan-type TeacherMini array{id:int, firstName:string, lastName:string, email:string}|null
 */
final class ClassroomItemDto
{
    /**
     * @param array{id:int, firstName:string, lastName:string, email:string}|null $teacher
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?array $teacher, // TeacherMini|null
    ) {}
}
