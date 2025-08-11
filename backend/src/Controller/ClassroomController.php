<?php

namespace App\Controller;



use App\Entity\User;
use App\Service\ClassroomManager;
use App\Service\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ClassroomController handles API endpoints for classroom management.
 * Provides operations for classroom CRUD, teacher/student assignment, and classroom search.
 * Requires appropriate security roles for certain operations.
 */
#[Route('/api/classrooms')]
class ClassroomController extends AbstractController
{
    /**
     * Controller constructor.
     *
     * @param EntityManagerInterface $em Doctrine entity manager for database operations
     * @param UserManager $userManager Service for user-related operations
     * @param ClassroomManager $classroomManager Service for classroom management
     */
    public function __construct(
        private EntityManagerInterface $em,
        private UserManager $userManager,
        private ClassroomManager $classroomManager,
    ) {}

    /**
     * Returns a list of all classrooms.
     *
     * @return JsonResponse JSON response containing classroom data
     * @throws \JsonException If serialization fails
     */
    #[Route('', name: 'classroom_list', methods: ['GET'])]
    public function getAllClassrooms(): JsonResponse
    {
        $classrooms = $this->classroomManager->findAll();

        return $this->json(
            $classrooms,
            200,
            [],
            ['groups' => 'classroom:read']
        );
    }

    /**
     * Returns a list of unassigned classrooms.
     *
     * @return JsonResponse JSON response containing unassigned classroom data
     * @throws \JsonException If serialization fails
     */
    #[Route('/unassigned', name: 'classroom_unassigned', methods: ['GET'])]
    public function getUnassigned(): JsonResponse
    {
        $classrooms = $this->classroomManager->getUnassignedClassrooms();
        return $this->json($classrooms, 200, [], ['groups' => 'classroom:read']);
    }

    /**
     * Searches classrooms by name.
     *
     * @param Request $request The HTTP request containing the search name parameter
     * @return JsonResponse JSON response with search results or error message
     */
    #[Route('/search-class-by-name', name: 'classroom_name_search', methods: ['GET'])]
    public function searchClassByName(Request $request): JsonResponse
    {
        $name = $request->query->get('name');

        if (!$name) {
            return $this->json(['error' => 'Name parameter is required'], 400);
        }

        $classroom = $this->classroomManager->getClassByName($name);

        if (!$classroom) {
            return $this->json(['error' => "No classroom with the name \"$name\" found"], 404);
        }

        return $this->json($classroom, 200, [], ['groups' => 'classroom:read']);
    }

    /**
     * Unassigns all users (teacher and students) from a classroom.
     *
     * @param int $id The classroom ID to unassign users from
     * @return JsonResponse JSON response indicating success or error
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/unassign-all', name: 'classroom_unassign_all', methods: ['DELETE'])]
    public function unassignAllFromClassroom(int $id): JsonResponse
    {
        $classroom = $this->classroomManager->getClassById($id);

        if (!$classroom) {
            return $this->json(['error' => 'Classroom not found'], 404);
        }

        $this->classroomManager->unassignAll($classroom);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    /**
     * Assigns a teacher to a classroom.
     *
     * @param int $id The classroom ID
     * @param Request $request The HTTP request containing the teacher_id parameter
     * @return JsonResponse JSON response indicating success or error
     */
    #[Route('/{id}/assign-teacher', name: 'classroom_assign_teacher', methods: ['POST'])]
    public function assignTeacher(int $id, Request $request): JsonResponse
    {
        $classroom = $this->classroomManager->getClassById($id);
        $data = json_decode($request->getContent(), true);

        if (!isset($data['teacher_id']) || !is_numeric($data['teacher_id'])) {
            return $this->json(['error' => 'Invalid teacher ID'], 400);
        }

        $teacherId = (int) $data['teacher_id'];
        $teacher = $this->em->getRepository(User::class)->find($teacherId);

        if (!$classroom || !$teacher) {
            return $this->json(['error' => 'Classroom or Teacher not found'], 404);
        }

        if (!$teacher->isTeacher()) {
            return $this->json(['error' => 'User is not a teacher'], 400);
        }

        $this->classroomManager->assignTeacher($classroom, $teacher);
        return $this->json(['success' => true]);
    }

    /**
     * Retrieves classrooms taught by a specific teacher.
     *
     * @param int $id The teacher's ID
     * @return JsonResponse JSON response containing classroom data
     * @throws \JsonException If serialization fails
     */
    #[Route('/taught-by/{id}', name: 'classrooms_taught_by', methods: ['GET'])]
    public function getTaughtByTeacher(int $id): JsonResponse
    {
        $classrooms = $this->classroomManager->getFindByTeacher($id);

        return $this->json(
            ['data' => $classrooms],
            200,
            [],
            ['groups' => 'classroom:read']
        );
    }

    /**
     * Counts classrooms taught by a specific teacher.
     *
     * @param int $id The teacher's ID
     * @return JsonResponse JSON response with the count
     */
    #[Route('/taught-by-count/{id}', name: 'classrooms_taught_by_count', methods: ['GET'])]
    public function getTaughtByTeacherCount(int $id): JsonResponse
    {
        $count = $this->classroomManager->getCountByTeacher($id);

        return $this->json(
            ['count' => $count]
        );
    }

    /**
     * Retrieves the teacher assigned to a classroom.
     *
     * @param int $id The classroom ID
     * @return JsonResponse JSON response containing teacher data
     * @throws \JsonException If serialization fails
     */
    #[Route('/{id}/teacher', name: 'classroom_teacher', methods: ['GET'])]
    public function getTeacher(int $id): JsonResponse
    {
        $classroom = $this->classroomManager->getClassById($id);

        if (!$classroom) {
            return $this->json(['error' => 'Classroom not found'], 404);
        }

        $teacher = $classroom->getTeacher();
        return $this->json(
            ['teacher' => $teacher],
            200,
            [],
            ['groups' => 'classroom:read']
        );
    }

    /**
     * Unassigns the teacher from a classroom.
     *
     * @param int $id The classroom ID
     * @return JsonResponse JSON response indicating success or error
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/unassign-teacher', name: 'classroom_unassign_teacher', methods: ['DELETE'])]
    public function unassignTeacherFromClassroom(int $id): JsonResponse
    {
        $classroom = $this->classroomManager->getClassById($id);

        if (!$classroom) {
            return $this->json(['error' => 'Classroom not found'], 404);
        }

        $classroom->setTeacher(null);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    /**
     * Assigns a student to a classroom.
     *
     * @param int $id The classroom ID
     * @param Request $request The HTTP request containing the student_id parameter
     * @return JsonResponse JSON response indicating success or error
     */
    #[Route('/{id}/assign-student', name: 'classroom_assign_student', methods: ['POST'])]
    public function assignStudent(int $id, Request $request): JsonResponse
    {
        $classroom = $this->classroomManager->getClassById($id);
        $data = json_decode($request->getContent(), true);

        if (!isset($data['student_id']) || !is_numeric($data['student_id'])) {
            return $this->json(['error' => 'Invalid student ID'], 400);
        }

        $studentId = (int) $data['student_id'];
        $student = $this->userManager->getUserById($studentId);

        if (!$classroom || !$student) {
            return $this->json(['error' => 'Classroom or Student not found'], 404);
        }

        if (!$student->isStudent()) {
            return $this->json(['error' => 'User is not a student'], 400);
        }

        $this->classroomManager->assignStudent($classroom, $student);
        return $this->json(['success' => true]);
    }

    /**
     * Retrieves students assigned to a classroom.
     *
     * @param int $id The classroom ID
     * @return JsonResponse JSON response containing student data
     * @throws \JsonException If serialization fails
     */
    #[Route('/{id}/students', name: 'classroom_students', methods: ['GET'])]
    public function getStudents(int $id): JsonResponse
    {
        $classroom = $this->classroomManager->getClassById($id);

        if (!$classroom) {
            return $this->json(['error' => 'Classroom not found'], 404);
        }

        $students = $classroom->getStudents();

        return $this->json(
            ['students' => $students],
            200,
            [],
            ['groups' => 'classroom:read']
        );
    }

    /**
     * Retrieves classrooms a student is enrolled in.
     *
     * @param int $id The student's ID
     * @return JsonResponse JSON response containing classroom data
     */
    #[Route('/enrolled-in/{id}', name: 'classroom_enrolled_in', methods: ['GET'])]
    public function getStudentEnrolledIn(int $id): JsonResponse
    {
        $classrooms = $this->classroomManager->getFindByStudent($id);

        return $this->json(
            ['class' => $classrooms],
            200,
            [],
            ['groups' => 'classroom:read']
        );
    }

    /**
     * Unassigns all students from a classroom.
     *
     * @param int $id The classroom ID
     * @return JsonResponse JSON response with success status and affected count
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/unassign-all-students', name: 'classroom_unassign_all_students', methods: ['DELETE'])]
    public function unassignAllStudentsFromClassroom(int $id): JsonResponse
    {
        $classroom = $this->classroomManager->getClassById($id);
        if (!$classroom) {
            return $this->json(['error' => 'Classroom not found'], 404);
        }

        $affected = $this->userManager->unassignAllStudentsFromClassroom($classroom);

        return $this->json(['success' => true, 'unassigned' => $affected], 200);
    }

    /**
     * Unassigns a specific student from a classroom.
     *
     * @param int $id The classroom ID
     * @param Request $request The HTTP request containing the studentId parameter
     * @return JsonResponse JSON response indicating success or error
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/unassign-student', name: 'classroom_unassign_student', methods: ['DELETE'])]
    public function unassignStudentFromClassroom(int $id, Request $request): JsonResponse
    {
        $classroom = $this->classroomManager->getClassById($id);

        if (!$classroom) {
            return $this->json(['error' => 'Classroom not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $studentId = $data['studentId'] ?? null;

        if (!$studentId) {
            return $this->json(['error' => 'Missing studentId in request body'], 400);
        }

        $student = $this->userManager->getUserInClassroom($studentId, $id);

        if (!$student) {
            return $this->json(['error' => 'Student not found'], 404);
        }

        if ($student->getClassroom()?->getId() !== $classroom->getId()) {
            return $this->json(['error' => 'Student is not assigned to this classroom'], 400);
        }

        $this->classroomManager->removeStudentFromClassroom($student);
        $this->em->persist($student);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    /**
     * Creates a new classroom.
     *
     * @param Request $request The HTTP request containing the classroom name
     * @return JsonResponse JSON response with the new classroom ID
     * @throws \JsonException If request content is invalid JSON
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/create-classroom', name: 'classroom_create', methods: ['POST'])]
    public function createClassroom(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $classroom = $this->classroomManager->createClassroom($data['name']);
        return new JsonResponse(['id' => $classroom->getId()], Response::HTTP_CREATED);
    }

    /**
     * Deletes a classroom and its associations.
     *
     * @param int $id The classroom ID to delete
     * @return JsonResponse JSON response with success status and deleted classroom info
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/delete-classroom', name: 'classroom_delete', methods: ['DELETE'])]
    public function deleteClassroom(int $id): JsonResponse
    {
        $classroom = $this->classroomManager->getClassById($id);
        if (!$classroom) {
            return $this->json(['error' => 'Classroom not found'], 404);
        }

        $this->classroomManager->removeClassroom($classroom);

        return $this->json([
            'success' => true,
            'id' => $classroom->getId(),
            'name' => $classroom->getName()
        ]);
    }
}
