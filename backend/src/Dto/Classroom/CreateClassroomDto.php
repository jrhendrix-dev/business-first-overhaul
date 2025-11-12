<?php
// src/Dto/Classroom/CreateClassroomDto.php
declare(strict_types=1);

namespace App\Dto\Classroom;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateClassroomDto
{
    #[Assert\NotBlank]
    public string $name;

    /** Decimal price like 15.00; null = free */
    #[Assert\Type('numeric')]
    #[Assert\GreaterThanOrEqual(0)]
    public ?float $price = null;

    /** Optional currency, defaults to EUR */
    #[Assert\Currency]
    public ?string $currency = 'EUR';

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->name = isset($data['name']) ? (string)$data['name'] : '';
        $dto->price = array_key_exists('price', $data) && $data['price'] !== null
            ? (float)$data['price']
            : null;
        $dto->currency = array_key_exists('currency', $data) && $data['currency'] !== null
            ? (string)$data['currency']
            : 'EUR';
        return $dto;
    }
}
