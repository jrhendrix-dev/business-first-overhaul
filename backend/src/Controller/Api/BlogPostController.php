<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Blog\BlogPostCreateDto;
use App\Dto\Blog\BlogPostUpdateDto;
use App\Enum\BlogPostStatus;
use App\Mapper\Response\BlogPostResponseMapper;
use App\Repository\BlogPostRepository;
use App\Service\BlogPostManager;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * CRUD for teachers/admins.
 */
#[Route('/blog-posts')]
final class BlogPostController extends AbstractController
{
    public function __construct(
        private readonly BlogPostRepository $repo,
        private readonly BlogPostManager $manager,
        private readonly BlogPostResponseMapper $mapper,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_TEACHER')]
    public function list(Request $req): JsonResponse
    {
        $statusStr = $req->query->get('status');
        $status = $statusStr ? BlogPostStatus::tryFrom($statusStr) : null;

        $posts = $this->repo->listForAdmin($status);

        return $this->json($this->mapper->toDtoList($posts));
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function create(Request $req): JsonResponse
    {
        /** @var BlogPostCreateDto $dto */
        $dto = $this->deserialize($req, BlogPostCreateDto::class);
        $this->validateOrThrow($dto);

        /** @var User $author */
        $author = $this->getUser();

        $post = $this->manager->create($dto, $author, new \DateTimeImmutable());

        return $this->json($this->mapper->toDto($post), 201);
    }

    #[Route('/{id<\d+>}', methods: ['PUT'])]
    #[IsGranted('ROLE_TEACHER')]
    public function update(int $id, Request $req): JsonResponse
    {
        /** @var BlogPostUpdateDto $dto */
        $dto = $this->deserialize($req, BlogPostUpdateDto::class);
        $this->validateOrThrow($dto);

        /** @var User $actor */
        $actor = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $post = $this->manager->update($id, $dto, $actor, new \DateTimeImmutable(), $isAdmin);

        return $this->json($this->mapper->toDto($post));
    }

    #[Route('/{id<\d+>}/publish', methods: ['POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function publish(int $id): JsonResponse
    {
        /** @var User $actor */
        $actor = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $post = $this->manager->publish($id, $actor, new \DateTimeImmutable(), $isAdmin);

        return $this->json($this->mapper->toDto($post));
    }

    #[Route('/{id<\d+>}/unpublish', methods: ['POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function unpublish(int $id): JsonResponse
    {
        /** @var User $actor */
        $actor = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $post = $this->manager->unpublish($id, $actor, new \DateTimeImmutable(), $isAdmin);

        return $this->json($this->mapper->toDto($post));
    }

    #[Route('/{id<\d+>}', methods: ['DELETE'])]
    #[IsGranted('ROLE_TEACHER')]
    public function delete(int $id): JsonResponse
    {
        /** @var User $actor */
        $actor = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $this->manager->delete($id, $actor, $isAdmin);

        return $this->json(null, 204);
    }

    private function deserialize(Request $req, string $class): object
    {
        $data = json_decode($req->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $obj = new $class();
        foreach ($data as $k => $v) {
            if (property_exists($obj, $k)) {
                $obj->$k = $v;
            }
        }
        return $obj;
    }

    private function validateOrThrow(object $dto): void
    {
        $errors = $this->validator->validate($dto);
        if (count($errors) === 0) return;

        $details = [];
        foreach ($errors as $e) {
            $details[$e->getPropertyPath()] = $e->getMessage();
        }

        // Your global listener/subscriber should format this to:
        // { "error": { "code": "VALIDATION_FAILED", "details": {...} } }
        throw new \InvalidArgumentException(json_encode([
            'error' => [
                'code' => 'VALIDATION_FAILED',
                'details' => $details,
            ],
        ], JSON_THROW_ON_ERROR));
    }
}
