<?php

// src/Dto/Grade/UpdateGradeDTO.php
declare(strict_types=1);

namespace App\Dto\Grade;

use App\Enum\GradeComponentEnum;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for partial grade updates.
 */
final class UpdateGradeDto
{
    public function __construct(
        #[Assert\Type('numeric')]
        #[Assert\Range(min: 0)]
        public ?float $score = null,

        #[Assert\Type('numeric')]
        #[Assert\GreaterThan(0)]
        public ?float $maxScore = null,

        public ?GradeComponentEnum $component = null,
    ) {}

    public function hasChanges(): bool
    {
        return $this->score !== null || $this->maxScore !== null || $this->component !== null;
    }
}
