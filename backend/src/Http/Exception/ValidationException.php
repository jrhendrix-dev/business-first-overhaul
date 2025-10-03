<?php
// src/Http/Exception/ValidationException.php
declare(strict_types=1);

namespace App\Http\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Thrown when DTO validation fails. Carries normalized errors.
 */
final class ValidationException extends HttpException
{
    /** @param array<string,string> $details */
    public function __construct(private array $details)
    {
        parent::__construct(422, 'VALIDATION_FAILED');
    }

    /** @return array{error: array{code: string, details: array<string,string>}} */
    public function toPayload(): array
    {
        return [
            'error' => [
                'code' => 'VALIDATION_FAILED',
                'details' => $this->details,
            ],
        ];
    }
}
