<?php
declare(strict_types=1);

namespace App\Mapper\Response\Contracts;

use App\Entity\Enrollment;
use App\Entity\User;

/**
 * Presenter port for “classrooms of a student” responses.
 */
interface StudentClassroomResponsePort
{
    /**
     * @param list<Enrollment> $enrollments
     * @return list<array<string,mixed>>
     */
    public function toCollection(array $enrollments): array;
}
