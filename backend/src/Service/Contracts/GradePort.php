<?php
// src/Service/Contracts/GradePort.php
declare(strict_types=1);

namespace App\Service\Contracts;

use App\Dto\Grade\UpdateGradeDTO;
use App\Entity\Enrollment;
use App\Entity\Grade;
use App\Entity\User;
use App\Enum\GradeComponentEnum;

interface GradePort
{
    public function addGrade(Enrollment $enrollment, GradeComponentEnum $component, float $score, float $maxScore): Grade;
    public function addGradeByIds(int $studentId, int $classId, GradeComponentEnum $component, float $score, float $maxScore): Grade;

    public function updateGrade(Grade $grade, UpdateGradeDTO $dto): Grade;
    public function deleteGrade(Grade $grade): void;

    public function requireGrade(int $id): Grade;

    /** @return Grade[] */
    public function listByEnrollment(Enrollment $enrollment): array;
    /** @return Grade[] */
    public function listByIds(int $studentId, int $classId): array;
    /** @return Grade[] */
    public function listForStudent(User $student, ?int $classId = null): array;

    public function averagePercentForEnrollment(Enrollment $enrollment): float;
    public function averagePercentForIds(int $studentId, int $classId): float;

    /** @return Grade[] */
    public function listForClassOwnedByTeacher(User $teacher, int $classId): array;
    /** @return Grade[] */
    public function listAll(): array;
    /** @return Grade[] */
    public function listForAllClassesOwnedByTeacher(User $teacher): array;
}
