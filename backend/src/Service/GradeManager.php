<?php
// src/Service/GradeManager.php
namespace App\Service;

use App\Entity\Enrollment;
use App\Entity\Grade;
use App\Repository\GradeRepository;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;

final class GradeManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private GradeRepository $grades,
        private EnrollmentManager $enrollments
    ) {}

    public function addGrade(Enrollment $enrollment, string $component, float $score, float $maxScore = 10.0): Grade
    {
        if ($maxScore <= 0) {
            throw new DomainException('maxScore must be > 0');
        }
        if ($score < 0 || $score > $maxScore) {
            throw new DomainException('score must be within 0..maxScore');
        }

        $g = new Grade();
        $g->setEnrollment($enrollment);
        $g->setComponent($component);
        $g->setScore($score);
        $g->setMaxScore($maxScore);


        $this->em->persist($g);
        $this->em->flush();

        return $g;
    }

    public function addGradeByIds(int $studentId, int $classId, string $component, float $score, float $maxScore = 10.0): Grade
    {
        $enrollment = $this->enrollments->getByIdsOrFail($studentId, $classId);
        return $this->addGrade($enrollment, $component, $score, $maxScore);
    }

    /** @return Grade[] */
    public function listByEnrollment(Enrollment $enrollment): array
    {
        return $this->grades->listByEnrollment($enrollment);
    }

    /** @return Grade[] */
    public function listByIds(int $studentId, int $classId): array
    {
        $enrollment = $this->enrollments->getByIdsOrFail($studentId, $classId);
        return $this->listByEnrollment($enrollment);
    }

    public function averagePercentForEnrollment(Enrollment $enrollment): float
    {
        return $this->grades->averagePercentFor($enrollment);
    }

    public function averagePercentForIds(int $studentId, int $classId): float
    {
        $enrollment = $this->enrollments->getByIdsOrFail($studentId, $classId);
        return $this->averagePercentForEnrollment($enrollment);
    }
}


