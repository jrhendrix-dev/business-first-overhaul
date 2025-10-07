<?php
// src/Dto/Grade/AddGradeDto.php
declare(strict_types=1);


namespace App\Dto\Grade;

use App\Http\Exception\ValidationException;
use Symfony\Component\Validator\Constraints as Assert;
use App\Enum\GradeComponentEnum;

/**
 * DTO for creating a new grade entry.
 */
final class AddGradeDto
{
    public function __construct(
        #[Assert\NotNull]
        public GradeComponentEnum $component = GradeComponentEnum::QUIZ,

        #[Assert\NotNull]
        #[Assert\Type('numeric')]
        public float $score = 0.0,

        #[Assert\NotNull]
        #[Assert\Type('numeric')]
        #[Assert\GreaterThan(0)]
        public float $maxScore = 10.0,
    ) {}

    public static function fromArray(array $a): self
    {
        $enum = GradeComponentEnum::tryFromMixed((string)($a['component'] ?? ''));
        if (!$enum) {
            throw new ValidationException([
                'component' => sprintf('Invalid component. Allowed: %s', implode(', ', GradeComponentEnum::values()))
            ]);
        }

        return new self(
            component: $enum,
            score: (float)($a['score'] ?? 0),
            maxScore: (float)($a['maxScore'] ?? 10.0),
        );
    }
}

