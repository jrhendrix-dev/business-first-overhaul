<?php
declare(strict_types=1);

namespace App\Entity;

use App\Enum\BlogPostStatus;
use App\Repository\BlogPostRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Blog post entity.
 *
 * Stores CKEditor HTML content.
 */
#[ORM\Entity(repositoryClass: BlogPostRepository::class)]
#[ORM\Table(name: 'blog_posts')]
#[ORM\Index(columns: ['slug'], name: 'blog_posts_slug_idx')]
#[ORM\Index(columns: ['status', 'published_at'], name: 'blog_posts_status_published_idx')]
class BlogPost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $title;

    #[ORM\Column(length: 200, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $excerpt = null;

    #[ORM\Column(type: 'text')]
    private string $contentHtml;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImageUrl = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $author;

    #[ORM\Column(length: 20, enumType: BlogPostStatus::class)]
    private BlogPostStatus $status = BlogPostStatus::DRAFT;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $title,
        string $slug,
        string $contentHtml,
        User $author,
        \DateTimeImmutable $now,
        ?string $excerpt = null,
        ?string $coverImageUrl = null
    ) {
        $this->title = $title;
        $this->slug = $slug;
        $this->contentHtml = $contentHtml;
        $this->author = $author;
        $this->excerpt = $excerpt;
        $this->coverImageUrl = $coverImageUrl;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getSlug(): string { return $this->slug; }
    public function getExcerpt(): ?string { return $this->excerpt; }
    public function getContentHtml(): string { return $this->contentHtml; }
    public function getCoverImageUrl(): ?string { return $this->coverImageUrl; }
    public function getAuthor(): User { return $this->author; }
    public function getStatus(): BlogPostStatus { return $this->status; }
    public function getPublishedAt(): ?\DateTimeImmutable { return $this->publishedAt; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function update(
        string $title,
        string $slug,
        string $contentHtml,
        \DateTimeImmutable $now,
        ?string $excerpt = null,
        ?string $coverImageUrl = null
    ): void {
        $this->title = $title;
        $this->slug = $slug;
        $this->contentHtml = $contentHtml;
        $this->excerpt = $excerpt;
        $this->coverImageUrl = $coverImageUrl;
        $this->updatedAt = $now;
    }

    public function publish(\DateTimeImmutable $now): void
    {
        $this->status = BlogPostStatus::PUBLISHED;
        $this->publishedAt = $now;
        $this->updatedAt = $now;
    }

    public function unpublish(\DateTimeImmutable $now): void
    {
        $this->status = BlogPostStatus::DRAFT;
        $this->publishedAt = null;
        $this->updatedAt = $now;
    }

    public function archive(\DateTimeImmutable $now): void
    {
        $this->status = BlogPostStatus::ARCHIVED;
        $this->updatedAt = $now;
    }

    public function isAuthor(User $user): bool
    {
        return $this->author->getId() === $user->getId();
    }
}
