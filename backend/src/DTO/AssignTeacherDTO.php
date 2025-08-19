<?php

// src/DTO/AssignTeacherDTO.php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class AssignTeacherDTO
{
    public function __construct(
        #[Assert\NotNull] #[Assert\Positive]
        public int $teacherId = 0,
    ) {}

    public static function fromArray(array $a): self
    {
        return new self(
            teacherId: (int)($a['teacherId'] ?? 0),
        );
    }
}
