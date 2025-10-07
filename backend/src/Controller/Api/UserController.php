<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Enum\UserRoleEnum;
use App\Mapper\Response\UserResponseMapper;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Http\Exception\ValidationException;

/**
 * Read-only user endpoints available to authenticated users.
 *
 * Security model:
 * - No PII-oriented lookups (e.g. by email) to avoid leaking identifiers.
 * - Only exposes fields mapped by UserResponseMapper (no password/email hash/etc).
 */
#[IsGranted('ROLE_USER')]
#[Route('/users', name: 'users_')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserManager $users,
        private readonly UserResponseMapper $toResponse,
    ) {}

    /**
     * GET /users?name=&role=ROLE_STUDENT
     * Case-insensitive search by name, optional role filter.
     */
    #[Route('', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $name = (string) $request->query->get('name', '');
        $roleParam = (string) $request->query->get('role', '');

        $role = null;
        if ($roleParam !== '') {
            $role = UserRoleEnum::tryFrom($roleParam);
            if (!$role) {
                throw new ValidationException(['role' => 'Invalid role']);
            }
        }

        $entities = $this->users->getUserByName($name, $role) ?? [];
        $dtos = array_map(fn($u) => $this->toResponse->toResponse($u), $entities);

        // Always 200 with an array (empty array if nothing found)
        return $this->json($dtos, Response::HTTP_OK);
    }

    /**
     * GET /users/{id}
     * Safe projection of a single user by id.
     */
    #[Route('/{id<\d+>}', name: 'get_by_id', methods: ['GET'])]
    public function getById(int $id): JsonResponse
    {
        $user = $this->users->getUserById($id) ?? throw $this->createNotFoundException();
        return $this->json($this->toResponse->toResponse($user), Response::HTTP_OK);
    }
}
