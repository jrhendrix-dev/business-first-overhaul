<?php
// src/Controller/Admin/ClassroomAdminController.php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Classroom\AssignTeacherDto;
use App\Dto\Classroom\CreateClassroomDto;
use App\Dto\Classroom\RenameClassroomDto;
use App\Mapper\Response\Contracts\ClassroomResponsePort;
use App\Repository\ClassroomRepository;
use App\Service\ClassroomManager;
use App\Service\Contracts\EnrollmentPort;
use App\Service\UserManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route('/classrooms')]
final class ClassroomAdminController extends AbstractController
{
    public function __construct(
        private readonly ClassroomManager      $classrooms,
        private readonly UserManager           $users,
        private readonly ClassroomRepository   $classroomRepo,
        private readonly ClassroomResponsePort $mapper,
        private readonly ValidatorInterface    $validator,
        private readonly EnrollmentPort        $enrollments,
    ) {}

    #[Route('', name: 'admin_classroom_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = $this->classroomRepo->findAllWithTeacher(includeDropped: true);
        return $this->json($this->mapper->toCollection($items));
    }

    #[Route('/{id}', name: 'admin_classroom_get', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getOne(int $id): JsonResponse
    {
        $class = $this->classrooms->getClassById($id);
        if (!$class) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'details' => ['resource' => 'Classroom']]], 404);
        }

        $activeCount = $this->enrollments->countActiveByClassroom($class);
        return $this->json($this->mapper->toDetail($class, $activeCount));
    }

    #[Route('/taught-by/{id}', name: 'admin_classrooms_taught_by', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function taughtBy(int $id): JsonResponse
    {
        $user = $this->users->getUserById($id);
        if (!$user) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'details' => ['resource' => 'User']]], 404);
        }
        if (!$user->isTeacher()) {
            return $this->json(['error' => ['code' => 'VALIDATION_FAILED', 'details' => ['id' => 'User is not a teacher']]], 422);
        }

        $items = $this->classrooms->getFindByTeacher($id) ?? [];
        return $this->json($this->mapper->toCollection($items));
    }

    /**
     * Returns the ACTIVE classrooms where the given student is enrolled.
     *
     * Response (200):
     *   Array<ClassroomItemDto>
     *
     * Error (404):
     *   { "error": { "code": "NOT_FOUND", "details": { "resource": "User" } } }
     *
     * Error (422):
     *   { "error": { "code": "VALIDATION_FAILED", "details": { "id": "User is not a student" } } }
     */
    #[Route('/enrolled-in/{id}', name: 'admin_classrooms_enrolled_in', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function enrolledIn(int $id): JsonResponse
    {
        $user = $this->users->getUserById($id);
        if (!$user) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'details' => ['resource' => 'User']]], 404);
        }

        if (!$user->isStudent()) {
            return $this->json(['error' => ['code' => 'VALIDATION_FAILED', 'details' => ['id' => 'User is not a student']]], 422);
        }

        $items = $this->classrooms->getFindByStudent($id) ?? [];

        return $this->json($this->mapper->toCollection($items));
    }

    #[Route('/taught-by-count/{id}', name: 'admin_classrooms_taught_by_count', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function taughtByCount(int $id): JsonResponse
    {
        $user = $this->users->getUserById($id);
        if (!$user) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'details' => ['resource' => 'User']]], 404);
        }
        if (!$user->isTeacher()) {
            return $this->json(['error' => ['code' => 'VALIDATION_FAILED', 'details' => ['id' => 'User is not a teacher']]], 422);
        }

        $count = $this->classrooms->getCountByTeacher($id);
        return $this->json(['count' => (int) $count]);
    }

    #[Route('/unassigned', name: 'admin_classroom_unassigned', methods: ['GET'])]
    public function unassigned(): JsonResponse
    {
        $items = $this->classrooms->getUnassignedClassrooms();
        return $this->json($this->mapper->toCollection($items));
    }

    #[Route('/search', name: 'admin_classroom_search_by_name', methods: ['GET'])]
    public function searchByName(Request $request): JsonResponse
    {
        $name = trim((string) $request->query->get('name', ''));
        if ($name === '') {
            return $this->json(['error' => ['code' => 'VALIDATION_FAILED', 'details' => ['name' => 'Required']]], 400);
        }
        $items = $this->classroomRepo->findByNameWithTeacher($name, includeDropped: true);
        return $this->json($this->mapper->toCollection($items));
    }

    #[Route('/{id}/teacher', name: 'admin_classroom_assign_teacher', requirements: ['id' => '\\d+'], methods: ['PUT'])]
    public function assignTeacher(int $id, Request $request): JsonResponse
    {
        $class = $this->classrooms->getClassById($id);
        if (!$class) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'details' => ['resource' => 'Classroom']]], 404);
        }

        $dto = AssignTeacherDto::fromArray(json_decode($request->getContent() ?: '{}', true, 512, \JSON_THROW_ON_ERROR));
        $violations = $this->validator->validate($dto);
        if (\count($violations) > 0) {
            $details = [];
            foreach ($violations as $v) { $details[$v->getPropertyPath()] = $v->getMessage(); }
            return $this->json(['error' => ['code' => 'VALIDATION_FAILED', 'details' => $details]], 422);
        }

        $teacher = $this->users->getUserById($dto->teacherId);
        if (!$teacher) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'details' => ['resource' => 'User']]], 404);
        }
        if (!$teacher->isTeacher()) {
            return $this->json(['error' => ['code' => 'VALIDATION_FAILED', 'details' => ['teacherId' => 'User is not a teacher']]], 422);
        }

        // Let the service throw ClassroomInactiveException (mapped to 409 by ExceptionListener)
        $this->classrooms->assignTeacher($class, $teacher);

        $activeCount = $this->enrollments->countActiveByClassroom($class);
        return $this->json($this->mapper->toDetail($class, $activeCount));
    }

    // Accept PATCH (your Angular uses PATCH) and POST (backward compatibility).
    #[Route('/{id}/reactivate', name: 'admin_classroom_reactivate', requirements: ['id' => '\\d+'], methods: ['PATCH','POST'])]
    public function reactivate(int $id): JsonResponse
    {
        $class = $this->classrooms->getClassById($id);
        if (!$class) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'details' => ['resource' => 'Classroom']]], 404);
        }

        $this->classrooms->reactivate($class);

        $activeCount = $this->enrollments->countActiveByClassroom($class);
        return $this->json($this->mapper->toDetail($class, $activeCount));
    }

    #[Route('', name: 'admin_classroom_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = CreateClassroomDto::fromArray(json_decode($request->getContent() ?: '{}', true, 512, \JSON_THROW_ON_ERROR));
        $violations = $this->validator->validate($dto);
        if (\count($violations) > 0) {
            $details = [];
            foreach ($violations as $v) { $details[$v->getPropertyPath()] = $v->getMessage(); }
            return $this->json(['error' => ['code' => 'VALIDATION_FAILED', 'details' => $details]], 422);
        }

        try {
            $name = $this->classrooms->normalizeName($dto->name);
            if ($name === '' || $this->classroomRepo->findOneBy(['name' => $name])) {
                return $this->json(['error' => ['code' => 'CONFLICT', 'details' => ['name' => 'Classroom exists']]], 409);
            }
            $class = $this->classrooms->createClassroom($name);
            return $this->json($this->mapper->toDetail($class), 201);
        } catch (UniqueConstraintViolationException) {
            return $this->json(['error' => ['code' => 'CONFLICT', 'details' => ['name' => 'Classroom exists']]], 409);
        }
    }

    #[Route('/{id}', name: 'admin_classroom_rename', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function rename(int $id, Request $request): JsonResponse
    {
        $class = $this->classroomRepo->find($id);
        if (!$class) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'details' => ['resource' => 'Classroom']]], 404);
        }

        $dto = RenameClassroomDto::fromArray(json_decode($request->getContent() ?: '{}', true, 512, \JSON_THROW_ON_ERROR));
        $violations = $this->validator->validate($dto);
        if (\count($violations) > 0) {
            $details = [];
            foreach ($violations as $v) { $details[$v->getPropertyPath()] = $v->getMessage(); }
            return $this->json(['error' => ['code' => 'VALIDATION_FAILED', 'details' => $details]], 422);
        }

        $name = $this->classrooms->normalizeName($dto->name);
        $existing = $this->classroomRepo->findOneBy(['name' => $name]);
        if ($existing && $existing->getId() !== $class->getId()) {
            return $this->json(['error' => ['code' => 'CONFLICT', 'details' => ['name' => 'Classroom exists']]], 409);
        }

        $this->classrooms->rename($class, $name);
        return $this->json($this->mapper->toDetail($class));
    }

    #[Route('/{id}/teacher', name: 'admin_classroom_unassign_teacher', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function unassignTeacher(int $id): JsonResponse
    {
        $class = $this->classrooms->getClassById($id);
        if (!$class) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'details' => ['resource' => 'Classroom']]], 404);
        }
        $this->classrooms->unassignTeacher($class);
        return $this->json(['success' => true], 200);
    }

    #[Route('/{id}', name: 'admin_classroom_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $class = $this->classrooms->getClassById($id);
        if (!$class) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'details' => ['resource' => 'Classroom']]], 404);
        }
        $deleted = $this->classrooms->removeClassroom($class);
        if ($deleted) {
            return $this->json(['deleted' => true], 200);
        }

        return $this->json([
            'deleted'     => false,
            'softDeleted' => true,
            'status'      => $class->getStatus()->value,
        ], 200);
    }


    // POST /api/admin/classrooms/{id}/restore-roster
    #[Route('/{id}/restore-roster', name: 'admin_classroom_restore_roster', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function restoreRoster(int $id): JsonResponse
    {
        $class = $this->classrooms->getClassById($id);
        if (!$class) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'details' => ['resource' => 'Classroom']]], 404);
        }

        // Service throws ClassroomInactiveException → mapped to 409 by ExceptionListener
        $restored = $this->classrooms->restoreRoster($class);

        $activeCount = $this->enrollments->countActiveByClassroom($class);

        // Keep response consistent: return the standard detail + a "restored" meta field.
        $payload = [
            'restored' => (int) $restored,
            'item'     => $this->mapper->toDetail($class, $activeCount),
        ];

        return $this->json($payload, 200);
    }

    #[Route('/{id}/dropped-enrollments', name: 'admin_classroom_dropped_enrollments', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function droppedEnrollments(int $id): JsonResponse
    {
        $class = $this->classrooms->getClassById($id);
        if (!$class) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'details' => ['resource' => 'Classroom']]], 404);
        }

        // optional TTL (90 days). If you don’t want TTL here, pass null.
        $since = (new \DateTimeImmutable())->sub(new \DateInterval('P90D'));
        $list = $this->enrollments->listDroppedForClassroom($class, $since);

        // minimal payload
        $items = array_map(static function ($e) {
            return [
                'id'         => $e->getId(),
                'student'    => [
                    'id' => $e->getStudent()->getId(),
                    'name' => $e->getStudent()->getFullName(),
                    'email' => $e->getStudent()->getEmail(),
                ],
                'droppedAt'  => $e->getDroppedAt()?->format(DATE_ATOM),
                'status'     => $e->getStatus()->value,
            ];
        }, $list);

        return $this->json(['items' => $items]);
    }

    #[Route('/{id}/restore-banner/dismiss', name: 'admin_classroom_restore_banner_dismiss', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function dismissRestoreBanner(int $id): JsonResponse
    {
        $class = $this->classrooms->getClassById($id);
        if (!$class) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'details' => ['resource' => 'Classroom']]], 404);
        }
        $this->classrooms->dismissRestoreBanner($class);
        return $this->json(['ok' => true]);
    }


}
