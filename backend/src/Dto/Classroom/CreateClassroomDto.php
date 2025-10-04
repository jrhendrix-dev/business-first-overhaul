<?php
// src/Dto/Classroom/CreateClassroomDto.php
declare(strict_types=1);

namespace App\Dto\Classroom;

use Symfony\Component\Validator\Constraints as Assert;

/** Payload for creating a classroom. */
final class CreateClassroomDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name = '',
    ) {}

    /** @param array<string,mixed> $a */
    public static function fromArray(array $a): self
    {
        return new self(name: (string)($a['name'] ?? ''));
    }
}
