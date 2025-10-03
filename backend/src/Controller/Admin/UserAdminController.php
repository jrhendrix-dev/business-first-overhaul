<?php
// src/Controller/Admin/UserAdminController.php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\Exception\ValidationException;
use App\Mapper\Request\UserRequestMapper;
use App\Mapper\Response\UserResponseMapper;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted('ROLE_ADMIN')]
final class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly UserManager $userManager,
        private readonly UserRequestMapper $requestMapper,
        private readonly UserResponseMapper $responseMapper,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/api/admin/users', name: 'admin_users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = $this->requestMapper->fromRequest($request);

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $details = [];
            foreach ($violations as $v) {
                $details[$v->getPropertyPath()] = $v->getMessage();
            }
            throw new ValidationException($details);
        }

        $user = $this->userManager->createFromDto($dto);

        /** @var \App\Dto\User\UserResponseDto $resp */
        $resp = $this->responseMapper->toResponse($user);

        return $this->json($resp, 201);
    }
}
