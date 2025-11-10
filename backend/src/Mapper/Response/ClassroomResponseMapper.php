<?php
// src/Mapper/Response/ClassroomResponseMapper.php
declare(strict_types=1);

namespace App\Mapper\Response;

use App\Mapper\Response\Contracts\ClassroomResponsePort;
use App\Entity\Classroom;

final class ClassroomResponseMapper implements ClassroomResponsePort
{
    /** @param Classroom[] $items */
    public function toCollection(array $items): array
    {
        return \array_map(fn (Classroom $c) => $this->toItem($c), $items);
    }

    public function toDetail(Classroom $classroom, int $activeCount = 0): array
    {
        $t = $classroom->getTeacher();
        return [
            'id'             => $classroom->getId(),
            'name'           => $classroom->getName(),
            'teacher'        => $t ? [
                'id'        => $t->getId(),
                'firstName' => (string)$t->getFirstName(),
                'lastName'  => (string)$t->getLastName(),
                'email'     => (string)$t->getEmail(),
            ] : null,
            'priceCents'     => $classroom->getPriceCents(),
            'currency'       => $classroom->getCurrency(),
            'status'         => $classroom->getStatus()->value,
            'activeStudents' => $activeCount,
        ];
    }

    public function toItem(Classroom $classroom): array
    {
        $t = $classroom->getTeacher();
        return [
            'id'         => $classroom->getId(),
            'name'       => $classroom->getName(),
            'priceCents' => $classroom->getPriceCents(),
            'currency'   => $classroom->getCurrency(),
            'status'     => $classroom->getStatus()->value,
            'teacher'    => $t ? [
                'id'    => $t->getId(),
                'name'  => \trim(($t->getFirstName() ?? '').' '.($t->getLastName() ?? '')),
                'email' => (string)$t->getEmail(),
            ] : null,
        ];
    }
}
