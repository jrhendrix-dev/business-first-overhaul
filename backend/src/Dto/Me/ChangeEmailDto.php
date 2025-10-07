<?php
// src/Dto/Me/ChangeEmailDto.php
declare(strict_types=1);

namespace App\Dto\Me;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangeEmailDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public readonly string $email = '',

        #[Assert\NotBlank]
        public readonly string $password = '',
    ) {}
}
