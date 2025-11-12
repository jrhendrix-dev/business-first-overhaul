<?php
declare(strict_types=1);

namespace App\Mapper\Response\Contracts;

use App\Entity\Classroom;

/**
 * Presenter port for classrooms API payloads.
 */
interface ClassroomResponsePort
{
    /**
     * @param Classroom[] $items
     * @return array
     */
    public function toCollection(array $items): array;

    /**
     * @param Classroom $classroom
     * @param int $activeCount
     * @return array
     */
    public function toDetail(Classroom $classroom, int $activeCount = 0): array;


    public function toItem(Classroom $classroom): array;
}
