<?php
// src/Controller/GradeController.php
namespace App\Controller;

use App\DTO\AddGradeDTO;
use App\DTO\UpdateGradeDTO;
use App\Http\ValidationResponder;
use App\Repository\EnrollmentRepository;
use App\Repository\GradeRepository;
use App\Service\EnrollmentManager;
use App\Service\GradeManager;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
final class GradeController extends AbstractController
{
    public function __construct(
        private readonly GradeManager         $gradeManager,
        private readonly EnrollmentManager $enrollmentManager,
        private readonly GradeRepository      $gradeRepo,
        private readonly ValidatorInterface   $validator,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Add a grade to a specific enrollment.
     *
     * Body: { "component": "quiz 1", "score": 8.5, "maxScore": 10 }
     *
     * @param int     $enrollmentId
     * @param Request $request
     * @return JsonResponse
     * @throws \JsonException
     */
    #[Route('/enrollments/{enrollmentId}/grades', name: 'grades_add', methods: ['POST'])]
    public function add(int $enrollmentId, Request $request): JsonResponse
    {
        $enrollment = $this->enrollmentManager->getEnrollmentById($enrollmentId);

        $payload = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        $dto     = AddGradeDTO::fromArray($payload);
        $viol    = $this->validator->validate($dto);
        if (count($viol) > 0) {
            return ValidationResponder::bad($viol);
        }

        try {
            $g = $this->gradeManager->addGrade($enrollment, $dto->component, $dto->score, $dto->maxScore);
        } catch (DomainException $e) {
            // e.g. invalid score bounds
            return $this->json(['error' => $e->getMessage()], 422);
        }

        return $this->json([
            'id'        => $g->getId(),
            'component' => $g->getComponent(),
            'score'     => $g->getScore(),
            'maxScore'  => $g->getMaxScore(),
            'percent'   => $g->getPercent(),
        ], 201);
    }

    /**
     * Update a grade (partial).
     *
     * Body (any subset): { "score": 9.0, "maxScore": 10, "component": "quiz 1 (retake)" }
     *
     * @param int     $id
     * @param Request $request
     * @return JsonResponse
     * @throws \JsonException
     */
    #[Route('/grades/{id}', name: 'grades_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $grade = $this->gradeRepo->find($id);
        if (!$grade) {
            return $this->json(['error' => 'Grade not found'], 404);
        }

        $raw = trim((string) $request->getContent());
        if ($raw === '') {
            return $this->json(
                ['error' => 'Request body is empty. Provide at least one of: score, maxScore, component.'],
                400
            );
        }

        try {
            /** @var array<string,mixed> $body */
            $body = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->json(['error' => 'Invalid JSON payload.'], 400);
        }

        $body = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        $dto  = UpdateGradeDTO::fromArray($body);
        $viol = $this->validator->validate($dto);
        if (count($viol) > 0) {
            return ValidationResponder::bad($viol);
        }

        if ($dto->score !== null) {
            $grade->setScore($dto->score);
        }
        if ($dto->maxScore !== null) {
            $grade->setMaxScore($dto->maxScore);
        }
        if ($dto->component !== null) {
            $grade->setComponent($dto->component);
        }

        $this->em->flush();

        return $this->json(['message' => 'Grade updated']);
    }

    #[Route('/grades/{id}', name: 'grades_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $grade = $this->gradeRepo->find($id);
        if (!$grade) return $this->json(['error' => 'Grade not found'], 404);

        $this->em->remove($grade);
        $this->em->flush();

        return new JsonResponse(null, 204);
    }
}
