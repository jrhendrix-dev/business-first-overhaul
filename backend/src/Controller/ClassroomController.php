<?php

namespace App\Controller;

use App\Entity\Classroom;
use App\Entity\User;
use App\Service\ClassroomManager;
use App\Repository\ClassroomRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/api/classrooms')]
class ClassroomController extends AbstractController
{
    public function __construct(

        private EntityManagerInterface $em,
        private UserRepository $userRepo,
        private ClassroomManager $classroomManager,
        private ClassroomRepository $classroomRepo

    ) {}



    //                      CLASSROOM ENDPOINTS
    #[Route('', name: 'classroom_list', methods: ['GET'])]
    public function getAllClassrooms(): JsonResponse
    {
        $classrooms = $this->classroomRepo->findAll();

        return $this->json(
            $classrooms,
            200,
            [],
            ['groups' => 'classroom:read']
        );
    }

    #[Route('/unassigned', name: 'classroom_unassigned', methods: ['GET'])]
    public function getUnassigned(): JsonResponse
    {
        $classrooms = $this->classroomManager->getUnassignedClassrooms();
        return $this->json($classrooms, 200, [], ['groups' => 'classroom:read']);

    }

    #[Route('/search-class-by-name', name: 'classroom_name_search', methods: ['GET'])]
    public function searchClassByName(Request $request): JsonResponse
    {
        $name = $request->query->get('name');

        if (!$name) {
            return $this->json(['error' => 'Name parameter is required'], 400);
        }

        $classroom = $this->classroomRepo->searchByName($name);

        if (!$classroom) {
            return $this->json(['error' => 'No classroom with the name "$name" found'], 404);
        }

        return $this->json($classroom, 200, [], ['groups' => 'classroom:read']);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/unassign-all', name: 'classroom_unassign_all', methods: ['DELETE'])]
    public function unassignAllFromClassroom(int $id): JsonResponse
    {
        $classroom = $this->classroomRepo->find($id);

        if (!$classroom) {
            return $this->json(['error' => 'Classroom not found'], 404);
        }

        $this->classroomManager->unassignAll($classroom);

        $this->em->flush();

        return $this->json(['success' => true]);

    }

    //                    TEACHER ENDPOINTS

    #[Route('/{id}/assign-teacher', name: 'classroom_assign_teacher', methods: ['POST'])]
    public function assignTeacher(int $id, Request $request): JsonResponse
    {
        $classroom = $this->classroomRepo->find($id);
        $data = json_decode($request->getContent(), true);

        if (!isset($data['teacher_id']) || !is_numeric($data['teacher_id'])) {
            return $this->json(['error' => 'Invalid teacher ID'], 400);
        }

        $teacherId = (int) $data['teacher_id'];
        $teacher = $this->em->getRepository(User::class)->find($teacherId);

        if (!$classroom || !$teacher) {
            return $this->json(['error' => 'Classroom or Teacher not found'], 404);
        }

        if(!$teacher->isTeacher()){
            return $this->json(['error' => 'User is not a teacher'],400);
        }

        $this->classroomManager->assignTeacher($classroom, $teacher);
        return $this->json(['success' => true]);

    }

    #[Route('/taught-by/{id}', name: 'classrooms_taught_by', methods: ['GET'])]
    public function getTaughtByTeacher(int $id): JsonResponse
    {

        $classrooms = $this->classroomRepo->findByTeacher($id);

        return $this->json(
            ['data' => $classrooms],
            200,
            [],
            ['groups' => 'classroom:read']
        );

    }

    #[Route('/taught-by-count/{id}', name: 'classrooms_taught_by', methods: ['GET'])]
    public function getTaughtByTeacherCount(int $id): JsonResponse
    {
        $count = $this->classroomRepo->countByTeacher($id);

        return $this->json(
            ['count' => $count]
        );
    }

    #[Route('/{id}/teacher', name: 'classroom_teacher', methods: ['GET'])]
    public function getTeacher(int $id): JsonResponse
    {
        $classroom = $this->classroomRepo->find($id);

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

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/unassign-teacher', name: 'classroom_unassign_teacher', methods: ['DELETE'])]
    public function unassignTeacherFromClassroom(int $id): JsonResponse
    {
        $classroom = $this->classroomRepo->find($id);

        if (!$classroom) {
            return $this->json(['error' => 'Classroom not found'], 404);
        }

        // Unassign teacher
        $classroom->setTeacher(null);

        $this->em->flush();

        return $this->json(['success' => true]);

    }

    //                  STUDENT ENDPOINTS

    #[Route('/{id}/assign-student', name: 'classroom_assign_student', methods: ['POST'])]
    public function assignStudent(int $id, Request $request): JsonResponse
    {
        $classroom = $this->classroomRepo->find($id);
        $data = json_decode($request->getContent(), true);

        if (!isset($data['student_id']) || !is_numeric($data['student_id'])) {
            return $this->json(['error' => 'Invalid student ID'], 400);
        }

        $studentId = (int) $data['student_id'];
        $student = $this->em->getRepository(User::class)->find($studentId);

        if (!$classroom || !$student) {
            return $this->json(['error' => 'Classroom or Student not found'], 404);
        }

        if (!$student->isStudent()) {
            return $this->json(['error' => 'User is not a student'], 400);
        }

        $this->classroomManager->assignStudent($classroom, $student);
        return $this->json(['success' => true]);
    }


    #[Route('/{id}/students', name: 'classroom_students', methods: ['GET'])]
    public function getStudents(int $id): JsonResponse
    {
        $classroom = $this->classroomRepo->find($id);

        if (!$classroom) {
            return $this->json(['error' => 'Classroom not found'], 404);
        }

        $students = $classroom->getStudents();

        return $this->json(
            ['students' => $students],
            200,
            [],
            ['groups' => 'classroom:read'] // This ensures proper serialization
        );
    }

    #[Route('/enrolled-in/{id}', name: 'classroom_enrolled_in', methods: ['GET'])]
    public function getStudentEnrolledIn(int $id): JsonResponse
    {

        $classrooms = $this->classroomRepo->findByStudent($id);

        return $this->json(
            ['class' => $classrooms],
            200,
            [],
            ['groups' => 'classroom:read']
        );

    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/unassign-all-students', name: 'classroom_unassign_all_students', methods: ['DELETE'])]
    public function unassignAllStudentsFromClassroom(int $id): JsonResponse
    {
        $classroom = $this->classroomRepo->find($id);

        if (!$classroom) {
            return $this->json(['error' => 'Classroom not found'], 404);
        }

        // Unassign students
        foreach ($classroom->getStudents() as $student) {
            $student->setClassroom(null);
        }

        $this->em->flush();

        return $this->json(['success' => true]);

    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/unassign-student', name: 'classroom_unassign_student', methods: ['DELETE'])]
    public function unassignStudentFromClassroom(int $id, Request $request): JsonResponse
    {


        $classroom = $this->classroomRepo->find($id);

        if (!$classroom) {
            return $this->json(['error' => 'Classroom not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $studentId = $data['studentId'] ?? null;

        if (!$studentId) {
            return $this->json(['error' => 'Missing studentId in request body'], 400);
        }

        $student = $this->userRepo->findStudentInClassroom($studentId, $id);

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


}
