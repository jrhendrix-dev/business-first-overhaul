<?php
// src/Http/ExceptionListener.php
declare(strict_types=1);

namespace App\Http;

use App\Domain\Classroom\Exception\ClassroomInactiveException;
use App\Http\Exception\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Maps domain/HTTP exceptions into consistent API JSON payloads.
 */
final class ExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();

        // Domain → 409
        if ($e instanceof ClassroomInactiveException) {
            $payload = [
                'error' => [
                    'code' => 'CLASSROOM_INACTIVE',
                    'details' => ['status' => $e->status()],
                ],
            ];
            $event->setResponse(new JsonResponse($payload, Response::HTTP_CONFLICT));
            return;
        }

        // DTO validation → 422
        if ($e instanceof ValidationException) {
            $event->setResponse(new JsonResponse($e->toPayload(), 422));
            return;
        }

        // If it is another HttpException, return its code but canonicalize the payload.
        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            $payload = ['error' => ['code' => $e->getMessage() ?: 'HTTP_ERROR', 'details' => []]];
            $event->setResponse(new JsonResponse($payload, $status));
            return;
        }

        // Fallback 500 with minimal leak
        $event->setResponse(new JsonResponse([
            'error' => [
                'code' => 'INTERNAL_SERVER_ERROR',
                'details' => [],
            ],
        ], 500));
    }
}
