<?php
// src/Controller/Admin/StudentAdminController.php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Student\StudentClassroomItemDto;
use App\Mapper\Response\StudentClassroomResponseMapper;
use App\Service\Contracts\EnrollmentPort;
use App\Service\EnrollmentManager;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Admin, student-centric endpoints.
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/students')]
final class StudentAdminController extends AbstractController
{
    public function __construct(
        private readonly UserManager $users,
        private readonly EnrollmentPort $enrollments,
        private readonly StudentClassroomResponseMapper $mapper,
    ) {}

    /**
     * List ACTIVE classrooms a student is enrolled in.
     *
     * GET /api/admin/students/{id}/classrooms
     *
     * @return JsonResponse<StudentClassroomItemDto[]>
     */
    #[Route('/{id}/classrooms', name: 'admin_students_classrooms', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function classroomsForStudent(int $id): JsonResponse
    {
        $user = $this->users->getUserById($id);
        if (!$user) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'details' => ['resource' => 'User']]], 404);
        }

        // Optional but useful guard
        if (!$user->isStudent()) {
            return $this->json([
                'error' => [
                    'code' => 'VALIDATION_FAILED',
                    'details' => ['role' => 'User is not a student'],
                ],
            ], 422);
        }

        $items = $this->enrollments->getActiveForStudent($user); // Enrollment[]
        $dto   = $this->mapper->toCollection($items);
        $count = \count($dto);

        // Keep list shape, add meta for clarity
        $payload = ['data' => $dto, 'meta' => ['count' => $count]];
        if ($count === 0) {
            $payload['meta']['message'] = 'Student has no active enrollments';
        }

        return $this->json($payload, 200);
    }
}
