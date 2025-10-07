<?php
// src/Controller/Admin/UserAdminController.php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\User\CreateUserDto;
use App\Dto\User\UpdateUserDto;
use App\Entity\Classroom;
use App\Enum\UserRoleEnum;
use App\Http\Exception\ValidationException;
use App\Mapper\Request\UserCreateRequestMapper;
use App\Mapper\Request\UserUpdateRequestMapper;
use App\Mapper\Response\UserResponseMapper;
use App\Service\Contracts\EnrollmentPort;
use App\Service\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;

#[Route('/users')]
#[IsGranted('ROLE_ADMIN')]
final class UserAdminController extends AbstractController
{
    public function __construct(
        private readonly UserManager $users,
        private readonly ValidatorInterface $validator,
        private readonly UserResponseMapper $toResponse,
        private readonly UserCreateRequestMapper $createMapper,
        private readonly UserUpdateRequestMapper $updateMapper,
        private readonly EntityManagerInterface $em,
        private readonly EnrollmentPort $enrollments,
    ) {}

    #[Route('', name: 'admin_users_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $entities = $this->users->getAllUsers();
        $dtos = array_map(fn($u) => $this->toResponse->toResponse($u), $entities);

        return $this->json($dtos, Response::HTTP_OK);
    }

    #[Route('', name: 'admin_users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var CreateUserDto $dto */
        $dto = $this->createMapper->fromRequest($request);

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $details = [];
            foreach ($violations as $v) { $details[$v->getPropertyPath()] = $v->getMessage(); }
            throw new ValidationException($details);
        }

        $user = $this->users->createUser(
            $dto->firstName, $dto->lastName, $dto->email, $dto->userName, $dto->password,
            UserRoleEnum::from($dto->role)
        );

        return $this->json($this->toResponse->toResponse($user), Response::HTTP_CREATED);
    }

    #[Route('/{id<\d+>}', name: 'admin_users_update', methods: ['PATCH'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $this->users->getUserById($id) ?? throw $this->createNotFoundException();

        /** @var UpdateUserDto $dto */
        $dto = $this->updateMapper->fromRequest($request);

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $details = [];
            foreach ($violations as $v) { $details[$v->getPropertyPath()] = $v->getMessage(); }
            throw new ValidationException($details);
        }

        $updated = $this->users->updateUser($user, $dto);

        return $this->json($this->toResponse->toResponse($updated), Response::HTTP_OK);
    }

    #[Route('/{id<\d+>}', name: 'admin_users_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->users->getUserById($id) ?? throw $this->createNotFoundException();
        $this->users->removeUser($user);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * GET /users/{id}
     * Get a user by numeric id.
     */
    #[Route('/{id<\d+>}', name: 'admin_users_get', methods: ['GET'])]
    public function getById(int $id): JsonResponse
    {
        $user = $this->users->getUserById($id) ?? throw $this->createNotFoundException();
        return $this->json($this->toResponse->toResponse($user), Response::HTTP_OK);
    }


    /**
     * GET /users/by-email?email=...
     * Find a user by email.
     */
    #[Route('/by-email', name: 'admin_users_by_email', methods: ['GET'])]
    public function getByEmail(Request $request): JsonResponse
    {
        $email = (string) $request->query->get('email', '');
        if ($email === '') {
            throw new ValidationException(['email' => 'Required']);
        }
        $user = $this->users->getUserByEmail($email) ?? throw $this->createNotFoundException();
        return $this->json($this->toResponse->toResponse($user), Response::HTTP_OK);
    }

    /**
     * GET /users/search-in-classroom?studentId=..&classroomId=..
     * Verify a student belongs to a classroom.
     */
    #[Route('/search-in-classroom', name: 'admin_users_in_classroom', methods: ['GET'])]
    public function getUserInClassroom(Request $request): JsonResponse
    {
        $studentId   = (int) $request->query->get('studentId', 0);
        $classroomId = (int) $request->query->get('classroomId', 0);

        $errors = [];
        if ($studentId <= 0)   { $errors['studentId']   = 'Must be a positive integer'; }
        if ($classroomId <= 0) { $errors['classroomId'] = 'Must be a positive integer'; }
        if ($errors) { throw new ValidationException($errors); }

        $student = $this->users->getUserInClassroom($studentId, $classroomId);
        if (!$student) {
            // Preserve existing 404 behavior for consistency
            throw $this->createNotFoundException();
        }

        return $this->json($this->toResponse->toResponse($student), Response::HTTP_OK);
    }

    /**
     * GET /users/recently-registered?days=30
     * Return users registered in the last N days (default 30).
     */
    #[Route('/recently-registered', name: 'admin_users_recent', methods: ['GET'])]
    public function getRecentlyRegistered(Request $request): JsonResponse
    {
        $days = (int) $request->query->get('days', 30);
        if ($days <= 0) {
            throw new ValidationException(['days' => 'Must be a positive integer']);
        }

        $entities = $this->users->getRecentlyRegistered($days) ?? [];
        $dtos = array_map(fn($u) => $this->toResponse->toResponse($u), $entities);

        return $this->json($dtos, Response::HTTP_OK);
    }

    /**
     * GET /users/students
     * List all students.
     */
    #[Route('/students', name: 'admin_users_students', methods: ['GET'])]
    public function getAllStudents(): JsonResponse
    {
        $entities = $this->users->getAllStudents();
        $dtos = array_map(fn($u) => $this->toResponse->toResponse($u), $entities);

        return $this->json($dtos, Response::HTTP_OK);
    }

    /**
     * GET /users/teachers
     * List all teachers.
     */
    #[Route('/teachers', name: 'admin_users_teachers', methods: ['GET'])]
    public function getAllTeachers(): JsonResponse
    {
        $entities = $this->users->getAllTeachers();
        $dtos = array_map(fn($u) => $this->toResponse->toResponse($u), $entities);

        return $this->json($dtos, Response::HTTP_OK);
    }

    /**
     * GET /users/students/without-classroom
     * List students without classroom assignment.
     */
    #[Route('/students/without-classroom', name: 'admin_students_without_classroom', methods: ['GET'])]
    public function getStudentsWithoutClassroom(): JsonResponse
    {
        $entities = $this->users->getStudentsWithoutClassroom() ?? [];
        $dtos = array_map(fn($u) => $this->toResponse->toResponse($u), $entities);

        return $this->json($dtos, Response::HTTP_OK);
    }


    /**
     * GET /users/teachers/without-classroom
     * List teachers without classroom assignment.
     */
    #[Route('/teachers/without-classroom', name: 'admin_teachers_without_classroom', methods: ['GET'])]
    public function getTeachersWithoutClassroom(): JsonResponse
    {
        $entities = $this->users->getTeachersWithoutClassroom() ?? [];
        $dtos = array_map(fn($u) => $this->toResponse->toResponse($u), $entities);

        return $this->json($dtos, Response::HTTP_OK);
    }

    /**
     * GET /users/count-by-role?role=ROLE_STUDENT
     * Return aggregate count for the given role.
     */
    #[Route('/count-by-role', name: 'admin_users_count_by_role', methods: ['GET'])]
    public function getCountByRole(Request $request): JsonResponse
    {
        $roleInput = (string) $request->query->get('role', '');
        $role = UserRoleEnum::tryFrom($roleInput);
        if (!$role) {
            throw new ValidationException(['role' => 'Invalid role']);
        }

        $count = $this->users->getCountByRole($role);
        return $this->json(['role' => $role->value, 'count' => $count], Response::HTTP_OK);
    }

    /**
     * POST /users/classroom/{classroomId}/unassign-all
     * Bulk-unassign every student from the given classroom.
     */
    #[Route('/classroom/{classroomId<\d+>}/unassign-all', name: 'admin_unassign_all_students', methods: ['POST'])]
    public function unassignAllStudentsFromClassroom(int $classroomId): JsonResponse
    {
        // get a reference; throws 404 if row truly not there when flushed later
        $classroom = $this->em->getRepository(Classroom::class)->find($classroomId)
            ?? throw $this->createNotFoundException();

        // Drop all ACTIVE enrollments in one shot
        $this->enrollments->dropAllActiveForClassroom($classroom);

        // Keep it simple and idempotent
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

}
