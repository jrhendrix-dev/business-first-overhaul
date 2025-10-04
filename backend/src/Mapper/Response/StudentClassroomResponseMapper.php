<?php
// src/Mapper/Response/StudentClassroomResponseMapper.php
declare(strict_types=1);

namespace App\Mapper\Response;

use App\Dto\Student\StudentClassroomItemDto;
use App\Entity\Enrollment;

/**
 * Maps Enrollment rows (for a given student) to StudentClassroomItemDto.
 */
final class StudentClassroomResponseMapper
{
    public function toItem(Enrollment $e): StudentClassroomItemDto
    {
        $class   = $e->getClassroom();
        $teacher = $class->getTeacher();

        return new StudentClassroomItemDto(
            classId:    (int) $class->getId(),
            className:  (string) $class->getName(),
            status:     $e->getStatus()->value,
            enrolledAt: $e->getEnrolledAt()?->format(\DATE_ATOM),
            droppedAt:  $e->getDroppedAt()?->format(\DATE_ATOM),
            teacher: $teacher ? [
                'id'        => (int) $teacher->getId(),
                'firstName' => (string) $teacher->getFirstName(),
                'lastName'  => (string) $teacher->getLastName(),
                'email'     => (string) $teacher->getEmail(),
            ] : null
        );
    }

    /** @param Enrollment[] $items @return StudentClassroomItemDto[] */
    public function toCollection(array $items): array
    {
        return array_map(fn (Enrollment $e) => $this->toItem($e), $items);
    }
}
