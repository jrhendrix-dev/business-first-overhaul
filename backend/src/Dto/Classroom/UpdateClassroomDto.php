<?php
// src/Dto/Classroom/UpdateClassroomDto.php
declare(strict_types=1);

namespace App\Dto\Classroom;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateClassroomDto
{
    /** Optional new name; keep current if null (only if $setName=true) */
    public ?string $name = null;
    public bool $setName = false;

    /** Optional new decimal price; if $setPrice=true and price === null → make it free */
    #[Assert\Type('numeric')]
    #[Assert\GreaterThanOrEqual(0)]
    public ?float $price = null;
    public bool $setPrice = false;

    /** Optional currency; only applied when $setCurrency=true */
    #[Assert\Currency]
    public ?string $currency = null;
    public bool $setCurrency = false;

    public static function fromArray(array $data): self
    {
        $dto = new self();

        if (array_key_exists('name', $data)) {
            $dto->setName = true;
            $dto->name = $data['name'] !== null ? (string)$data['name'] : null;
        }

        if (array_key_exists('price', $data)) {
            $dto->setPrice = true;
            $dto->price = $data['price'] !== null ? (float)$data['price'] : null;
        }

        if (array_key_exists('currency', $data)) {
            $dto->setCurrency = true;
            $dto->currency = $data['currency'] !== null ? (string)$data['currency'] : null;
        }

        return $dto;
    }
}
