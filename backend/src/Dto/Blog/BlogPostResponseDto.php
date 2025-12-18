<?php
declare(strict_types=1);

namespace App\Dto\Blog;

use App\Enum\BlogPostStatus;

/**
 * Output DTO for blog posts (admin + public).
 */
final class BlogPostResponseDto
{
    public int $id;
    public string $title;
    public string $slug;
    public ?string $excerpt;
    public string $contentHtml;
    public ?string $coverImageUrl;
    public string $authorName;
    public BlogPostStatus $status;
    public ?string $publishedAt;
    public string $createdAt;
    public string $updatedAt;
}
