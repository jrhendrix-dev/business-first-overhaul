<?php
// src/DTO/AddGradeDTO.php
namespace App\Dto\Grade;

use Symfony\Component\Validator\Constraints as Assert;

final class AddGradeDTO
{
    public function __construct(
        #[Assert\NotBlank] #[Assert\Length(max: 64)]
        public string $component = '',

        #[Assert\NotNull] #[Assert\Type('numeric')]
        public float $score = 0.0,

        #[Assert\NotNull] #[Assert\Type('numeric')] #[Assert\GreaterThan(0)]
        public float $maxScore = 10.0,
    ) {}

    public static function fromArray(array $a): self
    {
        return new self(
            component: (string)($a['component'] ?? ''),
            score: (float)($a['score'] ?? 0),
            maxScore: (float)($a['maxScore'] ?? 10.0),
        );
    }
}

