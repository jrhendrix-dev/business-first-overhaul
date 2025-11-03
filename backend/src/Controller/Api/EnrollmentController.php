<?php
// src/Controller/Api/EnrollmentController.php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\EnrollmentManager;
use App\Service\RequestEntityResolver;
use App\Mapper\Response\EnrollmentResponseMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Public/API endpoints for enrollments (read-only).
 */
#[Route('/enrollments')]
final class EnrollmentController extends AbstractController
{
    public function __construct(
        private readonly EnrollmentManager $enrollmentManager,
        private readonly RequestEntityResolver $resolver,
        private readonly EnrollmentResponseMapper $enrollmentMapper,
    ) {}

    /**
     * GET /api/classrooms/{classId}/active-enrollments
     */
    #[Route(
        '/class/{classId}/active-enrollments',
        name: 'active_enrollments_list_public',
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
