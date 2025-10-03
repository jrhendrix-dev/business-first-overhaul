<?php
// src/Mapper/Contracts/RequestMapperInterface.php
declare(strict_types=1);

namespace App\Mapper\Contracts;

use Symfony\Component\HttpFoundation\Request;

/**
 * Maps an HTTP request into a typed Request DTO (input boundary).
 *
 * @template T of object
 */
interface RequestMapperInterface
{
    /**
     * @return object DTO instance
     */
    public function fromRequest(Request $request): object;
}
