<?php
// src/Controller/Admin/GradeAdminController.php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Grade\AddGradeDto;
use App\Dto\Grade\UpdateGradeDto;
use App\Http\Exception\ValidationException;
use App\Mapper\Request\GradeAddRequestMapper;
use App\Mapper\Request\GradeUpdateRequestMapper;
use App\Mapper\Response\GradeResponseMapper;
use App\Service\EnrollmentManager;
use App\Service\GradeManager;
use DomainException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Administrative grade endpoints (full access across students/classrooms).
 */
#[Route('/grades')]
#[IsGranted('ROLE_ADMIN')]
final class GradeAdminController extends AbstractController
{
    public function __construct(
        private readonly GradeManager $grades,
        private readonly EnrollmentManager $enrollments,
        private readonly ValidatorInterface $validator,
        private readonly GradeAddRequestMapper $addMapper,
        private readonly GradeUpdateRequestMapper $updateMapper,
        private readonly GradeResponseMapper $responseMapper,
    ) {
    }

    #[Route('/enrollments/{enrollmentId<\d+>}/grades', name: 'admin_grades_list', methods: ['GET'])]
    public function list(int $enrollmentId): JsonResponse
    {
        $enrollment = $this->enrollments->getEnrollmentById($enrollmentId);
        if (!$enrollment) {
            throw $this->createNotFoundException();
        }

        $items = $this->grades->listByEnrollment($enrollment);

        return $this->json($this->responseMapper->toAdminCollection($items), Response::HTTP_OK);
    }

    #[Route('/enrollments/{enrollmentId<\d+>}/grades', name: 'admin_grades_create', methods: ['POST'])]
    public function create(int $enrollmentId, Request $request): JsonResponse
    {
        $enrollment = $this->enrollments->getEnrollmentById($enrollmentId);
        if (!$enrollment) {
            throw $this->createNotFoundException();
        }

        /** @var AddGradeDto $dto */
        $dto = $this->addMapper->fromRequest($request);
        $this->assertValid($dto);

        try {
            $grade = $this->grades->addGrade(
                $enrollment,
                $dto->component,
                $dto->score,
                $dto->maxScore,
            );
        } catch (DomainException $exception) {
            return $this->json(
                ['error' => ['code' => 'VALIDATION_FAILED', 'details' => ['message' => $exception->getMessage()]]],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return $this->json($this->responseMapper->toAdminItem($grade), Response::HTTP_CREATED);
    }

    #[Route('/all', name: 'admin_grades_all', methods: ['GET'])]
    public function listAll(): JsonResponse
    {
        $items = $this->grades->listAll();
        return $this->json($this->responseMapper->toAdminCollection($items), Response::HTTP_OK);
    }

    #[Route('/{id<\d+>}', name: 'admin_grades_get', methods: ['GET'])]
    public function getOne(int $id): JsonResponse
    {
        try {
            $grade = $this->grades->requireGrade($id);
        } catch (RuntimeException) {
            throw $this->createNotFoundException();
        }

        return $this->json($this->responseMapper->toAdminItem($grade), Response::HTTP_OK);
    }

    #[Route('/{id<\d+>}', name: 'admin_grades_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $grade = $this->grades->requireGrade($id);
        } catch (RuntimeException) {
            throw $this->createNotFoundException();
        }

        /** @var UpdateGradeDto $dto */
        $dto = $this->updateMapper->fromRequest($request);
        $this->assertValid($dto);

        try {
            $updated = $this->grades->updateGrade($grade, $dto);
        } catch (DomainException $exception) {
            return $this->json(
                ['error' => ['code' => 'VALIDATION_FAILED', 'details' => ['message' => $exception->getMessage()]]],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return $this->json($this->responseMapper->toAdminItem($updated), Response::HTTP_OK);
    }

    #[Route('/{id<\d+>}', name: 'admin_grades_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $grade = $this->grades->requireGrade($id);
        } catch (RuntimeException) {
            throw $this->createNotFoundException();
        }

        $this->grades->deleteGrade($grade);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function assertValid(object $dto): void
    {
        $violations = $this->validator->validate($dto);
        if (count($violations) === 0) {
            return;
        }

        $details = [];
        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $details[$violation->getPropertyPath()] = $violation->getMessage();
        }

        throw new ValidationException($details);
    }
}
