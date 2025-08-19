<?php
// src/Controller/ClassroomController.php

namespace App\Controller;

use App\Service\ClassroomManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ClassroomController exposes read & CRUD endpoints for classrooms.
 * - Listing/searching classrooms
 * - Retrieving teacher assigned to a classroom (read-only)
 * - Creating and deleting classrooms
 *
 * Mutations that involve other aggregates are handled elsewhere:
 * - Teacher assignment/removal: ClassroomTeacherController
 * - Enrollments (students in classes): EnrollmentController
 */
#[Route('/api/classrooms')]
final class ClassroomController extends AbstractController
{
    public function __construct(
        private readonly ClassroomManager $classrooms
    ) {}

    /**
     * List all classrooms.
     *
     * @return JsonResponse
     */
    #[Route('', name: 'classroom_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = $this->classrooms->findAll();

        return $this->json($items, Response::HTTP_OK, [], ['groups' => 'classroom:read']);
    }

    /**
     * Get a single classroom by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'classroom_get', methods: ['GET'])]
    public function getOne(int $id): JsonResponse
    {
        $class = $this->classrooms->getClassById($id);
        if (!$class) {
            return $this->json(['error' => 'Classroom not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($class, Response::HTTP_OK, [], ['groups' => 'classroom:read']);
    }

    /**
     * List all classrooms without an assigned teacher.
     *
     * @return JsonResponse
     */
    #[Route('/unassigned', name: 'classroom_unassigned', methods: ['GET'])]
    public function unassigned(): JsonResponse
    {
        $items = $this->classrooms->getUnassignedClassrooms();

        return $this->json($items, Response::HTTP_OK, [], ['groups' => 'classroom:read']);
    }

    /**
     * Search classrooms by (partial) name.
     * Query param: ?name=<needle>
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/search', name: 'classroom_search_by_name', methods: ['GET'])]
    public function searchByName(Request $request): JsonResponse
    {
        $name = trim((string) $request->query->get('name', ''));
        if ($name === '') {
            return $this->json(['error' => 'Query parameter "name" is required'], Response::HTTP_BAD_REQUEST);
        }

        $items = $this->classrooms->getClassByName($name);
        if (!$items) {
            return $this->json(['error' => "No classroom with the name \"$name\" found"], Response::HTTP_NOT_FOUND);
        }

        return $this->json($items, Response::HTTP_OK, [], ['groups' => 'classroom:read']);
    }

    /**
     * Get all classrooms taught by a specific teacher.
     *
     * @param int $id Teacher ID
     * @return JsonResponse
     */
    #[Route('/taught-by/{id}', name: 'classrooms_taught_by', methods: ['GET'])]
    public function taughtBy(int $id): JsonResponse
    {
        $items = $this->classrooms->getFindByTeacher($id);

        return $this->json(['data' => $items], Response::HTTP_OK, [], ['groups' => 'classroom:read']);
    }

    /**
     * Count classrooms taught by a specific teacher.
     *
     * @param int $id Teacher ID
     * @return JsonResponse
     */
    #[Route('/taught-by-count/{id}', name: 'classrooms_taught_by_count', methods: ['GET'])]
    public function taughtByCount(int $id): JsonResponse
    {
        $count = $this->classrooms->getCountByTeacher($id);

        return $this->json(['count' => $count], Response::HTTP_OK);
    }

    /**
     * Get the currently assigned teacher for a classroom (read-only).
     *
     * @param int $id Classroom ID
     * @return JsonResponse
     */
    #[Route('/{id}/teacher', name: 'classroom_teacher', methods: ['GET'])]
    public function teacher(int $id): JsonResponse
    {
        $class = $this->classrooms->getClassById($id);
        if (!$class) {
            return $this->json(['error' => 'Classroom not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['teacher' => $class->getTeacher()], Response::HTTP_OK, [], ['groups' => 'classroom:read']);
    }

    /**
     * Create a new classroom.
     * Body: { "name": "A1" }
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \JsonException
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('', name: 'classroom_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        $name = trim((string)($data['name'] ?? ''));

        if ($name === '') {
            return $this->json(['error' => 'Field "name" is required'], Response::HTTP_BAD_REQUEST);
        }

        $class = $this->classrooms->createClassroom($name);

        return $this->json(['id' => $class->getId()], Response::HTTP_CREATED);
    }

    /**
     * Delete a classroom.
     *
     * @param int $id Classroom ID
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'classroom_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $class = $this->classrooms->getClassById($id);
        if (!$class) {
            return $this->json(['error' => 'Classroom not found'], Response::HTTP_NOT_FOUND);
        }

        $payload = ['success' => true, 'id' => $class->getId(), 'name' => $class->getName()];
        $this->classrooms->removeClassroom($class);

        return $this->json($payload, Response::HTTP_OK);
    }

    /**
     * Get all classrooms a student is enrolled in.
     * Result type is Classroom[], so it belongs here (repo joins Enrollment under the hood).
     *
     * @param int $id Student ID
     * @return JsonResponse
     */
    #[Route('/enrolled-in/{id}', name: 'classroom_enrolled_in', methods: ['GET'])]
    public function enrolledIn(int $id): JsonResponse
    {
        $classes = $this->classrooms->getFindByStudent($id);

        return $this->json(['class' => $classes], Response::HTTP_OK, [], ['groups' => 'classroom:read']);
    }
}
