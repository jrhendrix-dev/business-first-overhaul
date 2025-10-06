<?php
// src/Mapper/Response/GradeResponseMapper.php
declare(strict_types=1);

namespace App\Mapper\Response;

use App\Entity\Enrollment;
use App\Entity\Grade;
use App\Entity\User;

/**
 * Maps Grade aggregates to audience-specific arrays (so we can omit nulls/fields).
 */
final class GradeResponseMapper
{
    /** @return array<string,mixed> */
    public function toAdminItem(Grade $grade): array
    {
        return $this->map($grade, includeStudent: true);
    }

    /** @return array<string,mixed> */
    public function toTeacherItem(Grade $grade): array
    {
        return $this->map($grade, includeStudent: true);
    }

    /** @return array<string,mixed> */
    public function toStudentItem(Grade $grade): array
    {
        // No student, no raw component for student view
        return $this->map($grade, includeStudent: false, includeRawComponent: false);
    }

    /** @param Grade[] $grades @return array<int, array<string,mixed>> */
    public function toAdminCollection(array $grades): array
    {
        return array_map(fn(Grade $g) => $this->toAdminItem($g), $grades);
    }

    /** @param Grade[] $grades @return array<int, array<string,mixed>> */
    public function toTeacherCollection(array $grades): array
    {
        return array_map(fn(Grade $g) => $this->toTeacherItem($g), $grades);
    }

    /** @param Grade[] $grades @return array<int, array<string,mixed>> */
    public function toStudentCollection(array $grades): array
    {
        return array_map(fn(Grade $g) => $this->toStudentItem($g), $grades);
    }

    /**
     * @return array<string,mixed>
     */
    private function map(Grade $grade, bool $includeStudent, bool $includeRawComponent = true): array
    {
        $e = $grade->getEnrollment();
        if (!$e instanceof Enrollment) {
            throw new \LogicException('Grade must have an enrollment association.');
        }

        $class = $e->getClassroom();
        $payload = [
            'id'           => (int) $grade->getId(),
            // omit 'component' unless explicitly requested
            // 'component' => $includeRawComponent ? $grade->getComponent()->value : null,
            'componentLabel' => $grade->getComponent()->label(),
            'score'        => $grade->getScore(),
            'maxScore'     => $grade->getMaxScore(),
            'percent'      => $grade->getPercent(),
            'gradedAt'     => $grade->getGradedAt()->format(\DATE_ATOM),
            'enrollmentId' => (int) $e->getId(),
            'classroom'    => [
                'id'   => (int) $class->getId(),
                'name' => (string) $class->getName(),
            ],
        ];

        if ($includeRawComponent) {
            $payload['component'] = $grade->getComponent()->value;
        }

        if ($includeStudent) {
            $payload['student'] = $this->studentPayload($e->getStudent());
        }

        return $payload;
    }

    /**
     * @return array{id:int, firstName:string, lastName:string, email:string}
     */
    private function studentPayload(User $student): array
    {
        return [
            'id'        => (int) $student->getId(),
            'firstName' => (string) $student->getFirstName(),
            'lastName'  => (string) $student->getLastName(),
            'email'     => (string) $student->getEmail(),
        ];
    }
}
