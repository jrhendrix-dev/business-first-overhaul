<?php

namespace App\Service;

use App\Entity\Classroom;
use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Resolves request IDs into domain entities or throws HTTP exceptions.
 */
final readonly class RequestEntityResolver
{
    public function __construct(
        private ClassroomManager $classroomManager,
        private UserManager      $userManager,
    ) {}

    /**
     * @throws NotFoundHttpException
     */
    public function requireClassroom(int $classId): Classroom
    {
        $class = $this->classroomManager->getClassById($classId);
        if (!$class) {
            throw new NotFoundHttpException("Classroom {$classId} not found");
        }
        return $class;
    }

    /**
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     */
    public function requireStudent(int $studentId, bool $mustBeStudent = true): User
    {
        $student = $this->userManager->getUserById($studentId);
        if (!$student) {
            throw new NotFoundHttpException("User {$studentId} not found");
        }
        if ($mustBeStudent && !$student->isStudent()) {
            throw new BadRequestHttpException('User is not a student');
        }
        return $student;
    }

    /**
     * Resolve both; classroomId can be null to skip that part.
     *
     * @return array{student: User, classroom: ?Classroom}
     * @throws NotFoundHttpException|BadRequestHttpException
     */
    public function requireStudentAndOptionalClass(int $studentId, ?int $classroomId): array
    {
        $student = $this->requireStudent($studentId, true);
        $class   = $classroomId !== null ? $this->requireClassroom($classroomId) : null;

        return ['student' => $student, 'classroom' => $class];
    }
}
