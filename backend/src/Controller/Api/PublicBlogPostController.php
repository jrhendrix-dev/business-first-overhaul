<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Mapper\Response\BlogPostResponseMapper;
use App\Repository\BlogPostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public read-only blog endpoints.
 */
#[Route('/public/blog-posts')]
final class PublicBlogPostController extends AbstractController
{
    public function __construct(
        private readonly BlogPostRepository $repo,
        private readonly BlogPostResponseMapper $mapper,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $posts = $this->repo->listPublished(limit: 50, offset: 0);
        return $this->json($this->mapper->toDtoList($posts));
    }

    #[Route('/{slug}', methods: ['GET'])]
    public function bySlug(string $slug): JsonResponse
    {
        $post = $this->repo->findBySlug($slug);
        if (!$post || !$post->getStatus()->isPubliclyVisible()) {
            throw new \RuntimeException('BLOG_POST_NOT_FOUND');
        }

        return $this->json($this->mapper->toDto($post));
    }
}
