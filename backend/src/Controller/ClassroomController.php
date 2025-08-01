<?php

namespace App\Controller;

use App\Entity\Classroom;
use App\Entity\User;
use App\Service\ClassroomManager;
use App\Repository\ClassroomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



#[Route('/api/classrooms')]
class ClassroomController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ClassroomManager $classroomManager,
        private ClassroomRepository $classroomRepo
    ) {}

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

        $this->classroomManager->assignTeacher($classroom, $teacher);
        return $this->json(['success' => true]);

    }

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

        $this->classroomManager->assignStudent($classroom, $student);
        return $this->json(['success' => true]);
    }

    #[Route('/taught-by/{id}', name: 'classrooms_taught_by', methods: ['GET'])]
    public function getTaughtByTeacher(int $id): JsonResponse
    {
        $classrooms = $this->classroomRepo->findBy(['teacher' => $id]);

        return $this->json(
            ['data' => $classrooms],
            200,
            [],
            ['groups' => 'classroom:read']
        );

    }
}
