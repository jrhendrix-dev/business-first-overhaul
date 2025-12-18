<?php
declare(strict_types=1);

namespace App\Service;

use App\Dto\Blog\BlogPostCreateDto;
use App\Dto\Blog\BlogPostUpdateDto;
use App\Entity\BlogPost;
use App\Entity\User;
use App\Repository\BlogPostRepository;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Use-case manager for blog posts.
 */
final class BlogPostManager
{
    public function __construct(
        private readonly BlogPostRepository $repo,
        private readonly SluggerInterface $slugger,
    ) {}

    public function create(BlogPostCreateDto $dto, User $author, \DateTimeImmutable $now): BlogPost
    {
        $slug = $this->slugify($dto->title);

        $post = new BlogPost(
            title: $dto->title,
            slug: $slug,
            contentHtml: $dto->contentHtml,
            author: $author,
            now: $now,
            excerpt: $dto->excerpt,
            coverImageUrl: $dto->coverImageUrl
        );

        $this->repo->save($post);
        return $post;
    }

    public function update(int $id, BlogPostUpdateDto $dto, User $actor, \DateTimeImmutable $now, bool $isAdmin): BlogPost
    {
        $post = $this->repo->findById($id);
        if (!$post) {
            throw new \RuntimeException('BLOG_POST_NOT_FOUND');
        }

        if (!$isAdmin && !$post->isAuthor($actor)) {
            throw new AccessDeniedException('Not allowed to edit this post.');
        }

        $post->update(
            title: $dto->title,
            slug: $this->slugify($dto->title),
            contentHtml: $dto->contentHtml,
            now: $now,
            excerpt: $dto->excerpt,
            coverImageUrl: $dto->coverImageUrl
        );

        $this->repo->save($post);
        return $post;
    }

    public function publish(int $id, User $actor, \DateTimeImmutable $now, bool $isAdmin): BlogPost
    {
        $post = $this->repo->findById($id);
        if (!$post) {
            throw new \RuntimeException('BLOG_POST_NOT_FOUND');
        }

        if (!$isAdmin && !$post->isAuthor($actor)) {
            throw new AccessDeniedException('Not allowed to publish this post.');
        }

        $post->publish($now);
        $this->repo->save($post);

        return $post;
    }

    public function unpublish(int $id, User $actor, \DateTimeImmutable $now, bool $isAdmin): BlogPost
    {
        $post = $this->repo->findById($id);
        if (!$post) {
            throw new \RuntimeException('BLOG_POST_NOT_FOUND');
        }

        if (!$isAdmin && !$post->isAuthor($actor)) {
            throw new AccessDeniedException('Not allowed to unpublish this post.');
        }

        $post->unpublish($now);
        $this->repo->save($post);

        return $post;
    }

    public function delete(int $id, User $actor, bool $isAdmin): void
    {
        $post = $this->repo->findById($id);
        if (!$post) {
            throw new \RuntimeException('BLOG_POST_NOT_FOUND');
        }

        if (!$isAdmin && !$post->isAuthor($actor)) {
            throw new AccessDeniedException('Not allowed to delete this post.');
        }

        $this->repo->remove($post);
    }

    private function slugify(string $title): string
    {
        return strtolower((string) $this->slugger->slug($title)->trim('-'));
    }
}
