<?php
// src/Dto/Classroom/ClassroomDetailDto.php
declare(strict_types=1);

namespace App\Dto\Classroom;

/**
 * Detail model for a single classroom (extendable).
 *
 * @phpstan-type TeacherMini array{id:int, firstName:string, lastName:string, email:string}|null
 */
final class ClassroomDetailDto
{
    /**
     * @param array{id:int, firstName:string, lastName:string, email:string}|null $teacher
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?array $teacher, // TeacherMini|null
        /** @var int|null Count of active students if you want to expose it later */
        public ?int $activeStudents = null,
    ) {}
}
