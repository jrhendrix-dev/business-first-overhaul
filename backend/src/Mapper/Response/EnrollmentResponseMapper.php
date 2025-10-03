<?php
// src/Mapper/Response/EnrollmentResponseMapper.php
declare(strict_types=1);

namespace App\Mapper\Response;

use App\Dto\Enrollment\EnrollmentItemDto;
use App\Entity\Enrollment;

/**
 * Maps Enrollment entities to API-safe DTOs.
 */
final class EnrollmentResponseMapper
{
    /**
     * @return EnrollmentItemDto
     */
    public function toItem(Enrollment $e): EnrollmentItemDto
    {
        $student = $e->getStudent();
        $class   = $e->getClassroom();

        return new EnrollmentItemDto(
            id:         (int) $e->getId(),
            classId:    (int) $class->getId(),
            status:     $e->getStatus()->value,
            enrolledAt: $e->getEnrolledAt()?->format(\DATE_ATOM),
            droppedAt:  $e->getDroppedAt()?->format(\DATE_ATOM),
            student: [
                'id'        => (int) $student->getId(),
                'firstName' => (string) $student->getFirstName(),
                'lastName'  => (string) $student->getLastName(),
                'email'     => (string) $student->getEmail(),
            ],
        );
    }

    /**
     * @param Enrollment[] $items
     * @return EnrollmentItemDto[]
     */
    public function toCollection(array $items): array
    {
        return array_map(fn(Enrollment $e) => $this->toItem($e), $items);
    }
}
