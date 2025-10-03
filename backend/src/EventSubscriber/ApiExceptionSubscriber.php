<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use App\Http\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Converts thrown exceptions into consistent JSON API responses.
 *
 * - Maps ValidationException to:
 *   { "error": { "code": "VALIDATION_FAILED", "details": { field: message } } } with 422
 * - Maps common HttpExceptionInterface to:
 *   { "error": { "code": UPPER_SNAKE, "details": {} } }
 * - Maps any other Throwable to:
 *   { "error": { "code": "INTERNAL_ERROR", "details": {} } } with 500
 *
 * @psalm-type ErrorPayload=array{error: array{code: string, details: array<string,string>}}
 */
final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly bool $debug = false, // inject kernel.debug
    ) {}

    /** @return array<string, mixed> */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();

        // 1) Our explicit validation error (preferred path)
        if ($e instanceof ValidationException) {
            $payload = $e->toPayload(); // { error: { code: VALIDATION_FAILED, details: {...} } }
            $event->setResponse(new JsonResponse($payload, Response::HTTP_UNPROCESSABLE_ENTITY));
            return;
        }

        // 2) HTTP exceptions (Not Found, Forbidden, etc.)
        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();

            // map exception class/message to a stable CODE
            $code = match (true) {
                $e instanceof AccessDeniedHttpException       => 'FORBIDDEN',
                $status === Response::HTTP_NOT_FOUND          => 'NOT_FOUND',
                $status === Response::HTTP_UNAUTHORIZED       => 'UNAUTHORIZED',
                $status === Response::HTTP_METHOD_NOT_ALLOWED => 'METHOD_NOT_ALLOWED',
                $status === Response::HTTP_BAD_REQUEST        => 'BAD_REQUEST',
                default                                       => 'HTTP_ERROR',
            };

            $payload = [
                'error' => [
                    'code'    => $code,
                    'details' => $this->debug ? ['message' => $e->getMessage()] : [],
                ],
            ];

            $event->setResponse(new JsonResponse($payload, $status));
            return;
        }

        // 3) Fallback (unexpected error)
        $this->logger->error('Unhandled exception', [
            'exception' => $e,
            'message'   => $e->getMessage(),
        ]);

        $payload = [
            'error' => [
                'code'    => 'INTERNAL_ERROR',
                'details' => $this->debug ? ['message' => $e->getMessage()] : [],
            ],
        ];

        $event->setResponse(new JsonResponse($payload, Response::HTTP_INTERNAL_SERVER_ERROR));
    }
}
