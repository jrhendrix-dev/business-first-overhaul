<?php
// src/Controller/EnrollmentController.php
namespace App\Controller;

use App\Service\ClassroomManager;
use App\Service\EnrollmentManager;
use App\Service\RequestEntityResolver;
use DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/api')]
final class EnrollmentController extends AbstractController
{
    public function __construct(
        private readonly EnrollmentManager $enrollmentManager,
        private readonly ClassroomManager $classroomManager,
        private readonly RequestEntityResolver $resolver,
    ) {}

    /**
     * Enroll a student in a classroom (idempotent at domain level if you decide so).
     *
     * @param int $classId   Classroom ID
     * @param int $studentId Student ID
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/classes/{classId}/students/{studentId}',
        name: 'enrollments_enroll',
        requirements: ['classId' => '\d+', 'studentId' => '\d+'],
        methods: ['PUT']
    )]
    public function enroll(int $classId, int $studentId): JsonResponse
    {
        $class=$this->resolver->requireClassroom($classId);
        $student=$this->resolver->requireStudent($studentId);

        try {
            $en = $this->enrollmentManager->enrollByIds($class, $student);

            $isReactivation = $en->getEnrolledAt() !== null  // optional heuristic
                && $en->getStatus() === \App\Enum\EnrollmentStatusEnum::ACTIVE
                && $en->getDroppedAt() === null
                && $en->getId() !== null; // always true, but we’ll switch status by comparing pre/post if you kept it

            return $this->json([
                'id'        => $en->getId(),
                'studentId' => $studentId,
                'classId'   => $classId,
                'status'    => $en->getStatus()?->value ?? 'ACTIVE',
            ], $isReactivation ? 200 : 201);
        } catch (NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            // still used for the "already active" case
            return $this->json(['error' => $e->getMessage()], 409);
        }
    }

    /**
     * Soft-drop the ACTIVE enrollment for a student in a classroom.
     *
     * - 204 No Content: dropped successfully
     * - 404 Not Found: classroom | user | active enrollment not found
     * - 400 Bad Request: user isn’t a student
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route(
        '/classes/{classId}/students/{studentId}',
        name: 'enrollments_soft_drop',
        requirements: ['classId' => '\d+', 'studentId' => '\d+'],
        methods: ['DELETE']
    )]
    public function softDrop(int $classId, int $studentId): JsonResponse
    {
        $class=$this->resolver->requireClassroom($classId);
        $student=$this->resolver->requireStudent($studentId);

        // Delegate to the domain layer (this wraps EnrollmentManager::dropActiveForStudent)
        try {
            $this->classroomManager->removeStudentFromClassroom($student, $class);
        } catch (NotFoundHttpException) {
            // Thrown when no ACTIVE enrollment exists for this student in this class
            return $this->json(
                ['error' => 'Active enrollment not found for this student in the specified classroom'],
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Drop a student's ACTIVE enrollment in a classroom (hard delete, per your manager).
     *
     * @param int $classId
     * @param int $studentId
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/classes/{classId}/students/{studentId}/hard',
        name: 'enrollments_drop',
        requirements: ['classId' => '\d+', 'studentId' => '\d+'],
        methods: ['DELETE']
    )]
    public function drop(int $classId, int $studentId): JsonResponse
    {
        $class=$this->resolver->requireClassroom($classId);
        $student=$this->resolver->requireStudent($studentId);

        try {
            $this->enrollmentManager->softDropByIds($class, $student);
        } catch (NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
        return new JsonResponse(null, 204);
    }

    /**
     * Optional: bulk drop all ACTIVE enrollments in a classroom (soft or hard based on your service).
     *
     * @param int $classId
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/classes/{classId}/enrollments', name: 'enrollments_drop_all', requirements: ['classId' => '\d+'], methods: ['DELETE'])]
    public function dropAllInClass(int $classId): JsonResponse
    {
        $class=$this->resolver->requireClassroom($classId);
        $this->enrollmentManager->dropAllActiveForClassroom($class);
        return new JsonResponse(null, 204);
    }

    /**
     * List enrollments (with student projection) for a classroom.
     *
     * @param int $classId
     * @return JsonResponse
     */
    #[Route('/classes/{classId}/enrollments', name: 'enrollments_list', requirements: ['classId' => '\d+'], methods: ['GET'])]
    public function list(int $classId): JsonResponse
    {
        $class=$this->resolver->requireClassroom($classId);
        $items = $this->enrollmentManager->getAnyEnrollmentForClassroom($class);

        return $this->json(
            $items,
            Response::HTTP_OK,
            [],
            ['groups' => ['classroom:enrollments', 'user:mini']]
        );
    }

    /**
     * List active enrollments (with student projection) for a classroom.
     *
     * @param int $classId
     * @return JsonResponse
     */
    #[Route('/classes/{classId}/active-enrollments', name: 'active_enrollments_list', requirements: ['classId' => '\d+'], methods: ['GET'])]
    public function listActive(int $classId): JsonResponse
    {
        $class=$this->resolver->requireClassroom($classId);

        $items = $this->enrollmentManager->getActiveEnrollmentsForClassroom($class);

        return $this->json(
            $items,
            Response::HTTP_OK,
            [],
            ['groups' => ['classroom:enrollments', 'user:mini']]
        );
    }

}
