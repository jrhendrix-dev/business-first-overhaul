<?php
// src/Dto/Classroom/ClassroomDetailDto.php
declare(strict_types=1);

namespace App\Dto\Classroom;

/**
 * Detail model for a single classroom.
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

        /** Minor units (e.g. 1500 for €15.00). */
        public ?int $priceCents = null,

        /** ISO 4217 currency; default EUR. */
        public ?string $currency = 'EUR',

        /** Optional count of active students. */
        public ?int $activeStudents = null,
    ) {}
}
