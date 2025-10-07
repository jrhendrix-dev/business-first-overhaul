<?php
// src/Mapper/Contracts/ResponseMapperInterface.php
declare(strict_types=1);

namespace App\Mapper\Contracts;

/**
 * Maps a domain Entity (or aggregate) into a Response DTO (output boundary).
 *
 * @template T of object
 */
interface ResponseMapperInterface
{
    /**
     * @param object $source Entity/aggregate/value object
     * @return object Response DTO
     */
    public function toResponse(object $source): object;
}
