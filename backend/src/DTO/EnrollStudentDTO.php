<?php

// src/DTO/EnrollStudentDTO.php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class EnrollStudentDTO
{
    public function __construct(
        #[Assert\NotNull] #[Assert\Positive]
        public int $studentId = 0,
    ) {}

    public static function fromArray(array $a): self
    {
        return new self(
            studentId: (int)($a['studentId'] ?? 0),
        );
    }
}

