<?php
// src/Controller/Api/ClassroomController.php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Mapper\Response\ClassroomResponseMapper;
use App\Repository\ClassroomRepository;
use App\Service\ClassroomManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/classrooms')]
final class ClassroomController extends AbstractController
{
    public function __construct(
        private readonly ClassroomManager $classrooms,
        private readonly ClassroomRepository $classroomRepo,
        private readonly ClassroomResponseMapper $mapper,
    ) {}


    #[Route('', name: 'classroom_list_public', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = $this->classroomRepo->findAllWithTeacher();
        return $this->json($this->mapper->toCollection($items));
    }


    #[Route('/{id}', name: 'classroom_get_public', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getOne(int $id): JsonResponse
    {
        $class = $this->classrooms->getClassById($id);
        if (!$class || $class->isDropped()) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'details' => ['resource' => 'Classroom']]], 404);
        }
        return $this->json($this->mapper->toDetail($class));
    }


    #[Route('/search', name: 'classroom_search_public', methods: ['GET'])]
    public function searchByName(Request $request): JsonResponse
    {
        $name = trim((string) $request->query->get('name', ''));
        if ($name === '') {
            return $this->json(['error' => ['code' => 'VALIDATION_FAILED', 'details' => ['name' => 'Required']]], 400);
        }
        $items = $this->classroomRepo->findByNameWithTeacher($name);
        return $this->json($this->mapper->toCollection($items));
    }



}
