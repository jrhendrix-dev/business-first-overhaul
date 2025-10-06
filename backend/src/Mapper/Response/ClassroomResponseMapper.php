<?php
declare(strict_types=1);

namespace App\Mapper\Response;

use App\Entity\Classroom;
use App\Entity\User;
use App\Mapper\Response\Contracts\ClassroomResponsePort;

/**
 * Maps Classroom entities to API-safe arrays.
 */
final class ClassroomResponseMapper implements ClassroomResponsePort
{
    /**
     * @param Classroom[] $items
     * @return array<int, array{id:int,name:string,teacher:?array{ id:int, name:string },status:string}>
     */
    public function toCollection(array $items): array
    {
        $out = [];
        foreach ($items as $c) {
            $out[] = $this->toItem($c);
        }
        return $out;
    }

    /**
     * @return array{
     *   id:int,
     *   name:string,
     *   teacher:?array{id:int,name:string},
     *   activeStudents:int,
     *   status:string
     * }
     */
    public function toDetail(Classroom $classroom, int $activeCount = 0): array
    {
        $teacher = $classroom->getTeacher();

        return [
            'id'             => (int) $classroom->getId(),
            'name'           => (string) $classroom->getName(),
            'teacher'        => $teacher ? $this->teacherMini($teacher) : null,
            'activeStudents' => $activeCount,
            'status'         => $classroom->getStatus()->value,
        ];
    }

    /**
     * @return array{
     *   id:int,
     *   name:string,
     *   teacher:?array{id:int,name:string},
     *   status:string
     * }
     */
    public function toItem(Classroom $classroom): array
    {
        $teacher = $classroom->getTeacher();

        return [
            'id'      => (int) $classroom->getId(),
            'name'    => (string) $classroom->getName(),
            'teacher' => $teacher ? $this->teacherMini($teacher) : null,
            'status'  => $classroom->getStatus()->value,
        ];
    }

    /**
     * @return array{id:int,name:string}
     */
    private function teacherMini(User $teacher): array
    {
        return [
            'id'   => (int) $teacher->getId(),
            'name' => (string) ($teacher->getFirstName().' '.$teacher->getLastName()),
        ];
    }
}
