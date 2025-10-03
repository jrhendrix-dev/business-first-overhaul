<?php
// src/Controller/Admin/EnrollmentAdminController.php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\ClassroomManager;
use App\Service\EnrollmentManager;
use App\Service\RequestEntityResolver;
use App\Mapper\Response\EnrollmentResponseMapper;
use DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Admin endpoints for classroom enrollments.
 *
 * All routes here are served under /api/admin/... thanks to the
 * global "/api" prefix configured in your routing and the local "/admin" prefix.
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('')]
final class EnrollmentAdminController extends AbstractController
{
    public function __construct(
        private readonly EnrollmentManager $enrollmentManager,
        private readonly ClassroomManager $classroomManager,
        private readonly RequestEntityResolver $resolver,
        private readonly EnrollmentResponseMapper $enrollmentMapper,
    ) {}

    /**
     * Enroll (or reactivate) a student in a classroom. Idempotent.
     *
     * PUT /api/admin/classes/{classId}/students/{studentId}
     */
    #[Route(
        '/classes/{classId}/students/{studentId}',
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
            // e.g., business rule conflict
            return $this->json(
                ['error' => ['code' => 'CONFLICT', 'details' => ['message' => $e->getMessage()]]],
                Response::HTTP_CONFLICT
            );
        }
    }

    /**
     * Soft-drop the ACTIVE enrollment for a student in a classroom.
     *
     * DELETE /api/admin/classes/{classId}/students/{studentId}
     */
    #[Route(
        '/classes/{classId}/students/{studentId}',
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
     * Hard drop (if you keep the endpoint) — you’re currently delegating to soft drop.
     *
     * DELETE /api/admin/classes/{classId}/students/{studentId}/hard
     */
    #[Route(
        '/classes/{classId}/students/{studentId}/hard',
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
     * Bulk soft-drop all ACTIVE enrollments in a classroom.
     *
     * DELETE /api/admin/classes/{classId}/enrollments
     */
    #[Route(
        '/classes/{classId}/enrollments',
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
     * List enrollments (any status) for a classroom.
     *
     * GET /api/admin/classes/{classId}/enrollments
     */
    #[Route(
        '/classes/{classId}/enrollments',
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
     * List ACTIVE enrollments for a classroom.
     *
     * GET /api/admin/classes/{classId}/active-enrollments
     */
    #[Route(
        '/classes/{classId}/active-enrollments',
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
