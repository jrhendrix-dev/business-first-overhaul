<?php
// src/Controller/Api/Teacher/ClassroomTeacherController.php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Classroom;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Repository\ClassroomRepository;
use App\Repository\EnrollmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/teacher')]
#[IsGranted('ROLE_TEACHER')]
final class ClassroomTeacherController extends AbstractController
{
    public function __construct(
        private readonly Security $security,
        private readonly ClassroomRepository $classes,
        private readonly EnrollmentRepository $enrollments,
    ) {}

    // GET /api/teacher/classrooms
    #[Route('/classrooms', name: 'teacher_classes_list', methods: ['GET'])]
    public function listClasses(): JsonResponse
    {
        /** @var User $teacher */
        $teacher = $this->security->getUser();

        // Use repo helper to ensure ACTIVE only and ordering
        $rows = $this->classes->findActiveByTeacher($teacher);

        // findActiveByTeacher already returns scalar array (id, name)
        return $this->json($rows, Response::HTTP_OK);
    }

    // GET /api/teacher/classrooms/{classId}/students?status=active|all
    #[Route('/classrooms/{classId<\d+>}/students', name: 'teacher_class_roster', methods: ['GET'])]
    public function roster(int $classId, Request $request): JsonResponse
    {
        $class = $this->requireOwnedClass($classId);

        $status = strtolower((string)$request->query->get('status', 'active'));
        if ($status === 'all') {
            $rows = $this->enrollments->findBy(['classroom' => $class], ['enrolledAt' => 'ASC']);
        } else {
            // assumes you have a helper like findActiveByClassroom(Classroom $c)
            $rows = $this->enrollments->findActiveByClassroom($class);
        }

        $out = array_map(function (Enrollment $e) {
            $s = $e->getStudent();
            return [
                'enrollmentId' => (int)$e->getId(),
                'status'       => $e->getStatus()->value,
                'enrolledAt'   => $e->getEnrolledAt()?->format(\DATE_ATOM),
                'droppedAt'    => $e->getDroppedAt()?->format(\DATE_ATOM),
                'student'      => [
                    'id'        => (int)$s->getId(),
                    'firstName' => $s->getFirstName(),
                    'lastName'  => $s->getLastName(),
                    'email'     => $s->getEmail(),
                ],
            ];
        }, $rows);

        return $this->json($out, Response::HTTP_OK);
    }

    private function requireOwnedClass(int $classId): Classroom
    {
        /** @var User $teacher */
        $teacher = $this->security->getUser();

        $class = $this->classes->find($classId);
        if (!$class || $class->getTeacher()?->getId() !== $teacher->getId()) {
            throw $this->createAccessDeniedException();
        }
        return $class;
    }
}
