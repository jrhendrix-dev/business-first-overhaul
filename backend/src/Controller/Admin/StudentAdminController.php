<?php
// src/Controller/Admin/StudentAdminController.php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Enum\UserRoleEnum;
use App\Mapper\Response\Contracts\StudentClassroomResponsePort;   // <-- use the port
use App\Service\Contracts\EnrollmentPort;                         // <-- port for reads
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Admin endpoints related to students.
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/students')]
final class StudentAdminController extends AbstractController
{
    public function __construct(
        private readonly UserManager $users,
        private readonly EnrollmentPort $enrollments,
        private readonly StudentClassroomResponsePort $mapper,   // <-- interface
    ) {}

    /**
     * List ACTIVE classrooms for the given student.
     *
     * GET /api/admin/students/{id}/classrooms
     *
     * @param int $id Student id
     * @return JsonResponse
     */
    #[Route('/{id}/classrooms', name: 'admin_student_active_classrooms', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function listActiveClassroomsForStudent(int $id): JsonResponse
    {
        $student = $this->users->getUserById($id);
        if (!$student) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'details' => ['resource' => 'User']]], Response::HTTP_NOT_FOUND);
        }

        // If you enforce role semantics:
        if (method_exists($student, 'getRole') && $student->getRole() !== UserRoleEnum::STUDENT) {
            return $this->json(['error' => ['code' => 'VALIDATION_FAILED', 'details' => ['id' => 'User is not a student']]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Use the EnrollmentPort read method (name must match your port)
        // If your port is named differently (e.g., getActiveForStudent), change this call accordingly.
        $enrollments = $this->enrollments->getActiveForStudent($student);

        return $this->json($this->mapper->toCollection($enrollments));
    }
}
