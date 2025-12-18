<?php
declare(strict_types=1);

namespace App\Mapper\Response;

use App\Dto\Blog\BlogPostResponseDto;
use App\Entity\BlogPost;

/**
 * Maps BlogPost entities into BlogPostResponseDto.
 */
final class BlogPostResponseMapper
{
    public function toDto(BlogPost $post): BlogPostResponseDto
    {
        $dto = new BlogPostResponseDto();
        $dto->id = $post->getId() ?? 0;
        $dto->title = $post->getTitle();
        $dto->slug = $post->getSlug();
        $dto->excerpt = $post->getExcerpt();
        $dto->contentHtml = $post->getContentHtml();
        $dto->coverImageUrl = $post->getCoverImageUrl();
        $dto->authorName = $post->getAuthor()->getUserIdentifier();
        $dto->status = $post->getStatus();
        $dto->publishedAt = $post->getPublishedAt()?->format(\DateTimeInterface::ATOM);
        $dto->createdAt = $post->getCreatedAt()->format(\DateTimeInterface::ATOM);
        $dto->updatedAt = $post->getUpdatedAt()->format(\DateTimeInterface::ATOM);

        return $dto;
    }

    /**
     * @param BlogPost[] $posts
     * @return BlogPostResponseDto[]
     */
    public function toDtoList(array $posts): array
    {
        return array_map(fn (BlogPost $p) => $this->toDto($p), $posts);
    }
}
