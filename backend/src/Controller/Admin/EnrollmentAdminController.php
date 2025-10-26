<?php
// src/Controller/Admin/EnrollmentAdminController.php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\ClassroomManager;
use App\Service\Contracts\EnrollmentPort;
use App\Service\EnrollmentManager;
use App\Service\RequestEntityResolver;
use App\Mapper\Response\EnrollmentResponseMapper;
use DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Admin endpoints for managing classrooms enrollments.
 *
 * Routing
 * -------
 * Controllers under `App\Controller\Admin\` are prefixed with `/api/admin` by `config/routes.yaml`.
 * This class adds a local `/enrollments` prefix, so final paths are:
 *
 * - PUT    /api/admin/enrollments/class/{classId}/student/{studentId}
 * - DELETE /api/admin/enrollments/class/{classId}/student/{studentId}
 * - DELETE /api/admin/enrollments/class/{classId}/student/{studentId}/hard
 * - DELETE /api/admin/enrollments/class/{classId}/enrollments
 * - GET    /api/admin/enrollments/class/{classId}/enrollments
 * - GET    /api/admin/enrollments/class/{classId}/active-enrollments
 *
 * Security
 * --------
 * All routes require an authenticated user with ROLE_ADMIN.
 *
 * Error Contract
 * --------------
 * Validation / domain / not-found errors are normalized elsewhere to:
 * { "error": { "code": UPPER_SNAKE_CODE, "details": { ... } } }
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/enrollments')]
final class EnrollmentAdminController extends AbstractController
{
    /**
     * @param EnrollmentManager         $enrollmentManager   Domain service for enrollment workflows.
     * @param ClassroomManager          $classroomManager    Domain service for classrooms logic.
     * @param RequestEntityResolver     $resolver            Helper to resolve/require entities by id.
     * @param EnrollmentResponseMapper  $enrollmentMapper    Maps Enrollment entities to API arrays.
     */
    public function __construct(
        private readonly EnrollmentPort $enrollmentManager,
        private readonly ClassroomManager $classroomManager,
        private readonly RequestEntityResolver $resolver,
        private readonly EnrollmentResponseMapper $enrollmentMapper,
    ) {}

    /**
     * Enroll (or reactivate) a student in a classrooms (idempotent).
     *
     * Route: PUT /api/admin/enrollments/class/{classId}/student/{studentId}
     *
     * Behavior
     * - If the student is already actively enrolled, returns the current enrollment.
     * - If previously dropped/soft-deleted, reactivates it.
     * - Applies business rules in the domain layer (e.g., class capacity).
     *
     * Responses
     * - 201 Created: { id, studentId, classId, status }
     * - 404 Not Found (normalized): { "error": { "code": "NOT_FOUND", ... } } when class/student not found.
     * - 409 Conflict: { "error": { "code": "CONFLICT", "details": { "message": string } } } on domain rule conflict.
     *
     * @param int $classId    Classroom identifier.
     * @param int $studentId  Student identifier.
     * @return JsonResponse   Enrollment summary payload.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException When class or student does not exist.
     */
    #[Route(
        '/class/{classId}/student/{studentId}',
        name: 'admin_enrollments_enroll',
        requirements: ['classId' => '\d+', 'studentId' => '\d+'],
        methods: ['PUT']
    )]
    public function enroll(int $classId, int $studentId): JsonResponse
    {
        $class   = $this->resolver->requireClassroom($classId);
        $student = $this->resolver->requireStudent($studentId);

        try {
            $enrollment = $this->enrollmentManager->enrollByIds($class, $student);

            return $this->json([
                'id'        => $enrollment->getId(),
                'studentId' => $studentId,
                'classId'   => $classId,
                'status'    => $enrollment->getStatus()->value,
            ], Response::HTTP_CREATED);
        } catch (DomainException $e) {
            return $this->json(
                ['error' => ['code' => 'CONFLICT', 'details' => ['message' => $e->getMessage()]]],
                Response::HTTP_CONFLICT
            );
        }
    }

    /**
     * Soft-drop the ACTIVE enrollment for a student in a classrooms.
     *
     * Route: DELETE /api/admin/enrollments/class/{classId}/student/{studentId}
     *
     * Behavior
     * - Removes the student from the classrooms via the ClassroomManager (soft operation).
     *
     * Responses
     * - 204 No Content on success.
     * - 404 Not Found (normalized) if class or student does not exist or there is no active enrollment.
     *
     * @param int $classId    Classroom identifier.
     * @param int $studentId  Student identifier.
     * @return JsonResponse   Empty body with appropriate status code.
     */
    #[Route(
        '/class/{classId}/student/{studentId}',
        name: 'admin_enrollments_soft_drop',
        requirements: ['classId' => '\d+', 'studentId' => '\d+'],
        methods: ['DELETE']
    )]
    public function softDrop(int $classId, int $studentId): JsonResponse
    {
        $class   = $this->resolver->requireClassroom($classId);
        $student = $this->resolver->requireStudent($studentId);

        $this->classroomManager->removeStudentFromClassroom($student, $class);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Hard drop endpoint (currently delegates to the soft-drop semantics).
     *
     * Route: DELETE /api/admin/enrollments/class/{classId}/student/{studentId}/hard
     *
     * Responses
     * - 204 No Content on success.
     * - 404 Not Found (normalized) if class or student does not exist or there is no active enrollment.
     *
     * @param int $classId    Classroom identifier.
     * @param int $studentId  Student identifier.
     * @return JsonResponse   Empty body with appropriate status code.
     */
    #[Route(
        '/class/{classId}/student/{studentId}/hard',
        name: 'admin_enrollments_drop',
        requirements: ['classId' => '\d+', 'studentId' => '\d+'],
        methods: ['DELETE']
    )]
    public function drop(int $classId, int $studentId): JsonResponse
    {
        $class   = $this->resolver->requireClassroom($classId);
        $student = $this->resolver->requireStudent($studentId);

        $this->enrollmentManager->softDropByIds($class, $student);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Bulk soft-drop all ACTIVE enrollments in a classrooms.
     *
     * Route: DELETE /api/admin/enrollments/class/{classId}/enrollments
     *
     * Responses
     * - 204 No Content on success.
     * - 404 Not Found (normalized) if classrooms does not exist.
     *
     * @param int $classId  Classroom identifier.
     * @return JsonResponse Empty body with appropriate status code.
     */
    #[Route(
        '/class/{classId}/enrollments',
        name: 'admin_enrollments_drop_all',
        requirements: ['classId' => '\d+'],
        methods: ['DELETE']
    )]
    public function dropAllInClass(int $classId): JsonResponse
    {
        $class = $this->resolver->requireClassroom($classId);
        $this->enrollmentManager->dropAllActiveForClassroom($class);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * List enrollments (any status) for a classrooms.
     *
     * Route: GET /api/admin/enrollments/class/{classId}/enrollments
     *
     * Responses
     * - 200 OK: array of enrollments mapped by {@see EnrollmentResponseMapper::toCollection()}.
     * - 404 Not Found (normalized) if classrooms does not exist.
     *
     * @param int $classId  Classroom identifier.
     * @return JsonResponse Array of enrollments.
     */
    #[Route(
        '/class/{classId}/enrollments',
        name: 'admin_enrollments_list',
        requirements: ['classId' => '\d+'],
        methods: ['GET']
    )]
    public function list(int $classId): JsonResponse
    {
        $class = $this->resolver->requireClassroom($classId);
        $items = $this->enrollmentManager->getAnyEnrollmentForClassroom($class);

        return $this->json($this->enrollmentMapper->toCollection($items));
    }

    /**
     * List ACTIVE enrollments for a classrooms.
     *
     * Route: GET /api/admin/enrollments/class/{classId}/active-enrollments
     *
     * Responses
     * - 200 OK: array of active enrollments mapped by {@see EnrollmentResponseMapper::toCollection()}.
     * - 404 Not Found (normalized) if classrooms does not exist.
     *
     * @param int $classId  Classroom identifier.
     * @return JsonResponse Array of active enrollments.
     */
    #[Route(
        '/class/{classId}/active-enrollments',
        name: 'admin_active_enrollments_list',
        requirements: ['classId' => '\d+'],
        methods: ['GET']
    )]
    public function listActive(int $classId): JsonResponse
    {
        $class = $this->resolver->requireClassroom($classId);
        $items = $this->enrollmentManager->getActiveEnrollmentsForClassroom($class);

        return $this->json($this->enrollmentMapper->toCollection($items));
    }
}
