<?php
// src/Dto/Classroom/RenameClassroomDto.php
declare(strict_types=1);

namespace App\Dto\Classroom;

use Symfony\Component\Validator\Constraints as Assert;

/** Payload for renaming a classroom. */
final class RenameClassroomDto
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
