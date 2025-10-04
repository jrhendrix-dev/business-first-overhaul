<?php
// src/Controller/Admin/ClassroomAdminController.php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Classroom\CreateClassroomDto;
use App\Dto\Classroom\RenameClassroomDto;
use App\Mapper\Response\ClassroomResponseMapper;
use App\Mapper\Response\Contracts\ClassroomResponsePort;
use App\Repository\ClassroomRepository;
use App\Service\ClassroomManager;
use App\Service\Contracts\EnrollmentPort;
use App\Service\UserManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Repository\EnrollmentRepository;

#[IsGranted('ROLE_ADMIN')]
#[Route('/classrooms')]
final class ClassroomAdminController extends AbstractController
{
    public function __construct(
        private readonly ClassroomManager        $classrooms,
        private readonly UserManager             $users,
        private readonly ClassroomRepository     $classroomRepo,
        private readonly ClassroomResponsePort   $mapper,
        private readonly ValidatorInterface      $validator,
        private readonly EnrollmentPort          $enrollments,
    ) {}

    #[Route('', name: 'admin_classroom_list', methods: ['GET'])]
    public function list(): JsonResponse
    {

        $items = $this->classroomRepo->findAllWithTeacher();
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

    // GET /api/admin/classrooms/taught-by/{id}
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

        $items = $this->classrooms->getFindByTeacher($id); // returns Classroom[]
        return $this->json($this->mapper->toCollection($items));
    }

    // GET /api/admin/classrooms/taught-by-count/{id}
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

    // GET /api/admin/students/{id}/classrooms
    #[Route('/../students/{id}/classrooms', // note: we are still inside /classrooms prefix; go up one level
        name: 'admin_classrooms_for_student',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]

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
        $items = $this->classroomRepo->findByNameWithTeacher($name);
        return $this->json($this->mapper->toCollection($items));
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

    // DELETE /api/admin/classrooms/{id}/teacher
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
        $this->classrooms->removeClassroom($class);
        return $this->json(['deleted' => true], 200);
    }
}
