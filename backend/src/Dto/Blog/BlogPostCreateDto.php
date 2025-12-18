<?php
declare(strict_types=1);

namespace App\Dto\Blog;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input DTO for creating a blog post.
 */
final class BlogPostCreateDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 180)]
    public string $title;

    #[Assert\NotBlank]
    public string $contentHtml;

    #[Assert\Length(max: 5000)]
    public ?string $excerpt = null;

    #[Assert\Url]
    #[Assert\Length(max: 255)]
    public ?string $coverImageUrl = null;
}
