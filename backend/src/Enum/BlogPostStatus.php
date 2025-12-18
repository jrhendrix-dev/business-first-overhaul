<?php
declare(strict_types=1);

namespace App\Enum;

/**
 * Lifecycle status for blog posts.
 */
enum BlogPostStatus: string
{
    case DRAFT = 'DRAFT';
    case PUBLISHED = 'PUBLISHED';
    case ARCHIVED = 'ARCHIVED';

    public function isPubliclyVisible(): bool
    {
        return $this === self::PUBLISHED;
    }
}
