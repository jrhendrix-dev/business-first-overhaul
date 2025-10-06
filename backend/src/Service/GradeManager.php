<?php
// src/Service/GradeManager.php
declare(strict_types=1);

namespace App\Service;

use App\Dto\Grade\UpdateGradeDTO;
use App\Entity\Enrollment;
use App\Entity\Grade;
use App\Entity\User;
use App\Entity\Classroom;
use App\Repository\ClassroomRepository;
use App\Enum\GradeComponentEnum;
use App\Repository\GradeRepository;
use App\Service\Contracts\EnrollmentPort;
use App\Service\Contracts\GradePort;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use RuntimeException;

/**
 * Coordinates grade workflows and enforces basic score invariants.
 */
final class GradeManager implements GradePort
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GradeRepository $grades,
        private readonly EnrollmentPort $enrollments,
        private readonly ClassroomRepository $classrooms,
    ) {
    }

    /**
     * Persist a new grade for the given enrollment.
     */
    public function addGrade(Enrollment $enrollment, GradeComponentEnum $component, float $score, float $maxScore): Grade
    {
        $this->assertScoreBounds($score, $maxScore);

        $grade = (new Grade())
            ->setEnrollment($enrollment)
            ->setComponent($component)
            ->setScore($score)
            ->setMaxScore($maxScore);

        $this->em->persist($grade);
        $this->em->flush();

        return $grade;
    }

    /**
     * Convenience wrapper for controllers that reference student/class ids.
     */
    public function addGradeByIds(int $studentId, int $classId, GradeComponentEnum $component, float $score, float $maxScore): Grade
    {
        $enrollment = $this->enrollments->getByIdsOrFail($studentId, $classId);
        return $this->addGrade($enrollment, $component, $score, $maxScore);
    }

    /**
     * Apply a partial update to an existing grade.
     */
    public function updateGrade(Grade $grade, UpdateGradeDTO $dto): Grade
    {
        if ($dto->score !== null) {
            $grade->setScore($dto->score);
        }
        if ($dto->maxScore !== null) {
            $grade->setMaxScore($dto->maxScore);
        }
        if ($dto->component !== null) {
            $grade->setComponent($dto->component);
        }

        $this->assertScoreBounds($grade->getScore(), $grade->getMaxScore());

        $this->em->flush();

        return $grade;
    }

    /**
     * Delete a grade permanently.
     */
    public function deleteGrade(Grade $grade): void
    {
        $this->em->remove($grade);
        $this->em->flush();
    }

    /**
     * Retrieve a grade by id or throw.
     */
    public function requireGrade(int $id): Grade
    {
        $grade = $this->grades->findOneWithRelations($id);
        if (!$grade instanceof Grade) {
            throw new RuntimeException(sprintf('Grade %d not found', $id));
        }

        return $grade;
    }

    /**
     * List grades for a specific enrollment.
     *
     * @return Grade[]
     */
    public function listByEnrollment(Enrollment $enrollment): array
    {
        return $this->grades->listByEnrollment($enrollment);
    }

    /**
     * List grades for a student in a class using scalar ids.
     *
     * @return Grade[]
     */
    public function listByIds(int $studentId, int $classId): array
    {
        $enrollment = $this->enrollments->getByIdsOrFail($studentId, $classId);
        return $this->listByEnrollment($enrollment);
    }

    /**
     * List grades across all enrollments for a student with optional classroom filter.
     *
     * @return Grade[]
     */
    public function listForStudent(User $student, ?int $classId = null): array
    {
        return $this->grades->listForStudent($student, $classId);
    }

    /**
     * Compute average percentage for the enrollment.
     */
    public function averagePercentForEnrollment(Enrollment $enrollment): float
    {
        return $this->grades->averagePercentFor($enrollment);
    }

    /**
     * Compute average percentage for student/class pair.
     */
    public function averagePercentForIds(int $studentId, int $classId): float
    {
        $enrollment = $this->enrollments->getByIdsOrFail($studentId, $classId);
        return $this->averagePercentForEnrollment($enrollment);
    }

    /**
     * Return all grades for a classroom, verifying teacher ownership.
     *
     * @return Grade[]
     */
    public function listForClassOwnedByTeacher(User $teacher, int $classId): array
    {
        /** @var Classroom|null $class */
        $class = $this->classrooms->find($classId);
        if (!$class) {
            throw new \RuntimeException('Classroom not found');
        }
        if (!$class->getTeacher() || $class->getTeacher()->getId() !== $teacher->getId()) {
            throw new \DomainException('You may only view grades for your own classrooms.');
        }

        return $this->grades->listForClass($classId);
    }

    /**
     * @return Grade[]
     */
    public function listAll(): array
    {
        return $this->grades->listAllWithRelations();
    }

    /**
     * @return Grade[]
     */
    public function listForAllClassesOwnedByTeacher(User $teacher): array
    {
        return $this->grades->listForTeacher($teacher);
    }

    private function assertScoreBounds(float $score, float $maxScore): void
    {
        if ($maxScore <= 0) {
            throw new DomainException('maxScore must be > 0');
        }
        if ($score < 0 || $score > $maxScore) {
            throw new DomainException('score must be within 0..maxScore');
        }
    }
}
