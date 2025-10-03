<?php

// src/DTO/UpdateGradeDTO.php
namespace App\DTO\Grade;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateGradeDTO
{
    public function __construct(
        #[Assert\Type('numeric')] #[Assert\Range(min: 0)]
        public ?float $score = null,

        #[Assert\Type('numeric')] #[Assert\GreaterThan(0)]
        public ?float $maxScore = null,

        #[Assert\Length(max: 64)]
        public ?string $component = null,
    ) {}

    public static function fromArray(array $a): self
    {
        return new self(
            score: isset($a['score']) ? (float)$a['score'] : null,
            maxScore: isset($a['maxScore']) ? (float)$a['maxScore'] : null,
            component: $a['component'] ?? null,
        );
    }
}
