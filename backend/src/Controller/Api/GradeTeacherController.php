<?php
// src/Controller/Api/Teacher/GradeTeacherController.php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Grade\AddGradeDto;
use App\Dto\Grade\UpdateGradeDto;
use App\Entity\Enrollment;
use App\Entity\Grade;
use App\Entity\User;
use App\Http\Exception\ValidationException;
use App\Mapper\Request\GradeAddRequestMapper;
use App\Mapper\Request\GradeUpdateRequestMapper;
use App\Mapper\Response\GradeResponseMapper;
use App\Service\EnrollmentManager;
use App\Service\GradeManager;
use DomainException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Teacher-facing grade endpoints enforcing classrooms ownership constraints.
 */
#[Route('/teacher')]
#[IsGranted('ROLE_TEACHER')]
final class GradeTeacherController extends AbstractController
{
    public function __construct(
        private readonly GradeManager $grades,
        private readonly EnrollmentManager $enrollments,
        private readonly GradeResponseMapper $responseMapper,
        private readonly GradeAddRequestMapper $addMapper,
        private readonly GradeUpdateRequestMapper $updateMapper,
        private readonly ValidatorInterface $validator,
        private readonly Security $security,
    ) {
    }

    #[Route('/classrooms/{classId<\d+>}/students/{studentId<\d+>}/grades', name: 'teacher_grades_list', methods: ['GET'])]
    public function list(int $classId, int $studentId): JsonResponse
    {
        $teacher    = $this->requireTeacher();
        $enrollment = $this->resolveEnrollment($studentId, $classId);
        $this->assertEnrollmentBelongsToTeacher($enrollment, $teacher);

        $items = $this->grades->listByEnrollment($enrollment);

        return $this->json($this->responseMapper->toTeacherCollection($items), Response::HTTP_OK);
    }

    #[Route('/classrooms/{classId<\d+>}/students/{studentId<\d+>}/grades', name: 'teacher_grades_create', methods: ['POST'])]
    public function create(int $classId, int $studentId, Request $request): JsonResponse
    {
        $teacher    = $this->requireTeacher();
        $enrollment = $this->resolveEnrollment($studentId, $classId);
        $this->assertEnrollmentBelongsToTeacher($enrollment, $teacher);

        /** @var AddGradeDto $dto */
        $dto = $this->addMapper->fromRequest($request);
        $this->assertValid($dto);

        try {
            $grade = $this->grades->addGrade($enrollment, $dto->component, $dto->score, $dto->maxScore);
        } catch (DomainException $exception) {
            return $this->json(
                ['error' => ['code' => 'VALIDATION_FAILED', 'details' => ['message' => $exception->getMessage()]]],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return $this->json($this->responseMapper->toTeacherItem($grade), Response::HTTP_CREATED);
    }

    #[Route('/grades/{id<\d+>}', name: 'teacher_grades_get', methods: ['GET'])]
    public function getOne(int $id): JsonResponse
    {
        $teacher = $this->requireTeacher();
        $grade   = $this->resolveGradeForTeacher($id, $teacher);

        return $this->json($this->responseMapper->toTeacherItem($grade), Response::HTTP_OK);
    }

    #[Route('/grades/{id<\d+>}', name: 'teacher_grades_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $teacher = $this->requireTeacher();
        $grade   = $this->resolveGradeForTeacher($id, $teacher);

        /** @var UpdateGradeDto $dto */
        $dto = $this->updateMapper->fromRequest($request);
        $this->assertValid($dto);

        try {
            $updated = $this->grades->updateGrade($grade, $dto);
        } catch (DomainException $exception) {
            return $this->json(
                ['error' => ['code' => 'VALIDATION_FAILED', 'details' => ['message' => $exception->getMessage()]]],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return $this->json($this->responseMapper->toTeacherItem($updated), Response::HTTP_OK);
    }

    #[Route('/grades/{id<\d+>}', name: 'teacher_grades_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $teacher = $this->requireTeacher();
        $grade   = $this->resolveGradeForTeacher($id, $teacher);

        $this->grades->deleteGrade($grade);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function assertValid(object $dto): void
    {
        $violations = $this->validator->validate($dto);
        if (count($violations) === 0) {
            return;
        }

        $details = [];
        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $details[$violation->getPropertyPath()] = $violation->getMessage();
        }

        throw new ValidationException($details);
    }

    private function resolveEnrollment(int $studentId, int $classId): Enrollment
    {
        try {
            return $this->enrollments->getByIdsOrFail($studentId, $classId);
        } catch (RuntimeException) {
            throw $this->createNotFoundException();
        }
    }

    private function resolveGradeForTeacher(int $id, User $teacher): Grade
    {
        try {
            $grade = $this->grades->requireGrade($id);
        } catch (RuntimeException) {
            throw $this->createNotFoundException();
        }

        $enrollment = $grade->getEnrollment();
        if (!$enrollment instanceof Enrollment) {
            throw $this->createNotFoundException();
        }

        $this->assertEnrollmentBelongsToTeacher($enrollment, $teacher);

        return $grade;
    }

    private function assertEnrollmentBelongsToTeacher(Enrollment $enrollment, User $teacher): void
    {
        $classroomTeacher = $enrollment->getClassroom()->getTeacher();
        if (!$classroomTeacher instanceof User || $classroomTeacher->getId() !== $teacher->getId()) {
            throw $this->createAccessDeniedException('You may only manage grades for your classrooms.');
        }
    }

    /**
     * List all grades in a classrooms (across all students) that belongs to the teacher.
     *
     * GET /api/teacher/classrooms/{classId}/grades
     */
    #[Route('/classrooms/{classId<\d+>}/grades', name: 'teacher_class_grades', methods: ['GET'])]
    public function listForClass(int $classId): JsonResponse
    {
        $teacher = $this->requireTeacher();

        try {
            $items = $this->grades->listForClassOwnedByTeacher($teacher, $classId);
        } catch (\RuntimeException) {
            // classrooms not found
            throw $this->createNotFoundException();
        } catch (\DomainException $e) {
            // not owned by teacher
            throw $this->createAccessDeniedException($e->getMessage());
        }

        // includes student info for teacher view
        return $this->json($this->responseMapper->toTeacherCollection($items), Response::HTTP_OK);
    }

    /**
     * All grades across all classrooms owned by the authenticated teacher.
     *
     * GET /api/teacher/classrooms/grades
     */
    #[Route('/classrooms/grades', name: 'teacher_all_classes_grades', methods: ['GET'])]
    public function listAllClassGrades(): JsonResponse
    {
        $teacher = $this->requireTeacher();
        $items   = $this->grades->listForAllClassesOwnedByTeacher($teacher);

        // Teacher view includes student info
        return $this->json($this->responseMapper->toTeacherCollection($items), Response::HTTP_OK);
    }

    private function requireTeacher(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || !$user->isTeacher()) {
            throw $this->createAccessDeniedException();
        }
        return $user;
    }

}
