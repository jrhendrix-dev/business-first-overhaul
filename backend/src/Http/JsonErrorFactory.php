<?php

namespace App\Http;

/**
 * Builds error payloads consistent with ApiExceptionSubscriber.
 *
 * @psalm-type ErrorPayload=array{error: array{code: string, details: array<string,string>}}
 */
final class JsonErrorFactory
{
    /**
     * @param array<string,string> $details
     * @return array{error: array{code: string, details: array<string,string>}}
     */
    public function make(string $code, array $details = []): array
    {
        return ['error' => ['code' => $code, 'details' => $details]];
    }
}
