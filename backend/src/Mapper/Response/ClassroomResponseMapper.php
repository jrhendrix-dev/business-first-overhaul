<?php
// src/Mapper/Response/ClassroomResponseMapper.php
declare(strict_types=1);

namespace App\Mapper\Response;

use App\Dto\Classroom\ClassroomDetailDto;
use App\Dto\Classroom\ClassroomItemDto;
use App\Entity\Classroom;
use App\Entity\User;

/**
 * Maps Classroom entities to API-safe DTOs.
 */
final class ClassroomResponseMapper
{
    /** @return array{id:int, firstName:string, lastName:string, email:string} */
    private function teacherMini(User $t): array
    {
        return [
            'id'        => (int) $t->getId(),
            'firstName' => (string) $t->getFirstName(),
            'lastName'  => (string) $t->getLastName(),
            'email'     => (string) $t->getEmail(),
        ];
    }

    public function toItem(Classroom $c): ClassroomItemDto
    {
        $teacher = $c->getTeacher(); // nullable in your model
        return new ClassroomItemDto(
            id: (int) $c->getId(),
            name: (string) $c->getName(),
            teacher: $teacher ? $this->teacherMini($teacher) : null,
        );
    }

    public function toDetail(Classroom $c, ?int $activeStudents = null): ClassroomDetailDto
    {
        $teacher = $c->getTeacher();
        return new ClassroomDetailDto(
            id: (int) $c->getId(),
            name: (string) $c->getName(),
            teacher: $teacher ? $this->teacherMini($teacher) : null,
            activeStudents: $activeStudents,
        );
    }

    /**
     * @param Classroom[] $items
     * @return ClassroomItemDto[]
     */
    public function toCollection(array $items): array
    {
        return array_map(fn(Classroom $c) => $this->toItem($c), $items);
    }
}
