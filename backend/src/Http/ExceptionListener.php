<?php
// src/Http/ExceptionListener.php (excerpt)
use App\Domain\Classroom\Exception\ClassroomInactiveException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\Response;

final class ExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();

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

        
    }
}
