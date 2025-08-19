<?php
// src/Controller/EnrollmentController.php
namespace App\Controller;

use App\Repository\ClassroomRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\UserRepository;
use App\Service\ClassroomManager;
use App\Service\EnrollmentManager;
use App\Service\UserManager;
use DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
final class EnrollmentController extends AbstractController
{
    public function __construct(
        private readonly EnrollmentManager $enrollmentManager,
        private readonly EnrollmentRepository $enrollments,
        private readonly ClassroomManager $classroomManager,
        private readonly ClassroomRepository $classrooms,
    ) {}

    /**
     * Enroll a student in a classroom (idempotent at domain level if you decide so).
     *
     * @param int $classId   Classroom ID
     * @param int $studentId Student ID
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/classes/{classId}/students/{studentId}', name: 'enrollments_enroll', methods: ['PUT'])]
    public function enroll(int $classId, int $studentId): JsonResponse
    {
        try {
            $en = $this->enrollmentManager->enrollByIds($studentId,$classId);
        } catch (NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            // e.g. "Student is already enrolled in this class."
            return $this->json(['error' => $e->getMessage()], 409);
        }

        return $this->json([
            'id'        => $en->getId(),
            'studentId' => $en->getStudent()->getId(),
            'classId'   => $en->getClassroom()->getId(),
            'status'    => $en->getStatus()?->value ?? 'ACTIVE',
        ], 201);
    }

    /**
     * Drop a student's ACTIVE enrollment in a classroom (hard delete, per your manager).
     *
     * @param int $classId
     * @param int $studentId
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/classes/{classId}/students/{studentId}', name: 'enrollments_drop', methods: ['DELETE'])]
    public function drop(int $classId, int $studentId): JsonResponse
    {
        try {
            $this->enrollmentManager->dropByIds($studentId, $classId);
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
    #[Route('/classes/{classId}/enrollments', name: 'enrollments_drop_all', methods: ['DELETE'])]
    public function dropAllInClass(int $classId): JsonResponse
    {
        $class = $this->classroomManager->getClassById($classId);
        if (!$class) {
            return $this->json(['error' => 'Classroom not found'], 404);
        }
        $this->enrollmentManager->dropAllActiveForClassroom($class);
        return new JsonResponse(null, 204);
    }

    /**
     * List enrollments (with student projection) for a classroom.
     *
     * @param int $classId
     * @return JsonResponse
     */
    #[Route('/classes/{classId}/enrollments', name: 'enrollments_list', methods: ['GET'])]
    public function list(int $classId): JsonResponse
    {
        $class = $this->classroomManager->getClassById($classId);
        if (!$class) {
            return $this->json(['error' => 'Classroom not found'], 404);
        }

        $items = $this->enrollments->findByClassroomId($classId);

        return $this->json([
            'items' => array_map(static function ($e) {
                return [
                    'enrollmentId' => $e->getId(),
                    'student' => [
                        'id'        => $e->getStudent()->getId(),
                        'firstName' => $e->getStudent()->getFirstname(),
                        'lastName'  => $e->getStudent()->getLastname(),
                        'email'     => $e->getStudent()->getEmail(),
                    ],
                    'enrolledAt' => $e->getEnrolledAt()->format(DATE_ATOM),
                    'status'     => $e->getStatus()?->value ?? 'ACTIVE',
                ];
            }, $items),
        ]);
    }
}
