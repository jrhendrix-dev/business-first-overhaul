<?php
// src/Controller/ClassroomTeacherController.php
namespace App\Controller;

use App\Dto\User\AssignTeacherDto;
use App\Http\Json;
use App\Http\ValidationResponder;
use App\Service\ClassroomManager;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/classrooms')]
final class ClassroomTeacherController extends AbstractController
{
    public function __construct(
        private readonly UserManager         $userManager,
        private readonly ClassroomManager    $classroomManager,
        private readonly ValidatorInterface  $validator
    ) {}

    /**
     * @throws \JsonException
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/teacher', name: 'classroom_set_teacher', methods: ['PUT'])]
    public function setTeacher(int $id, Request $request): JsonResponse
    {
        $class= $this->classroomManager->getClassById($id);
        if (!$class) {
            return $this->json(['error' => 'Classroom not found'], 404);
        }

        $body = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        $dto  = AssignTeacherDto::fromArray($body);
        $viol = $this->validator->validate($dto);
        if (count($viol) > 0) {
            return ValidationResponder::bad($viol);
        }

        $teacher = $this->userManager->getUserById($dto->teacherId);
        if (!$teacher) {
            return $this->json(['error' => 'Teacher not found'], 404);
        }

        if ($class->getTeacher() && $class->getTeacher()->getId() === $teacher->getId()) {
            return $this->json([
                'message'     => 'Teacher already assigned',
                'classroomId' => $class->getId(),
                'teacherId'   => $teacher->getId(),
            ], 200);
        }

        $this->classroomManager->assignTeacher($class, $teacher);
        return $this->json([
            'message'     => 'Teacher assigned',
            'classroomId' => $class->getId(),
            'teacherId'   => $teacher->getId()
        ], 200);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/teacher', name: 'classroom_remove_teacher', methods: ['DELETE'])]
    public function removeTeacher(int $id): JsonResponse
    {
        $class= $this->classroomManager->getClassById($id);

        if (!$class) {
            return $this->json(['error' => 'Classroom not found'], 404);
        }

        // Only remove teacher; do NOT drop enrollments here.
        $this->classroomManager->unassignTeacher($class);

        return new JsonResponse(null, 204); // idempotent
    }
}
