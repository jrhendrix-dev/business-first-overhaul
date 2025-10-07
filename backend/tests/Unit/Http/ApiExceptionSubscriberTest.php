<?php
// tests/Unit/Http/ApiExceptionSubscriberTest.php
declare(strict_types=1);

namespace App\Tests\Unit\Http;

use App\EventSubscriber\ApiExceptionSubscriber;
use App\Http\Exception\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ApiExceptionSubscriberTest extends TestCase
{
    #[Test]
    public function it_serializes_validation_exception_as_422(): void
    {
        $subscriber = new ApiExceptionSubscriber(new NullLogger(), false);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event  = new ExceptionEvent($kernel, new Request(), Kernel::MAIN_REQUEST, new ValidationException(['email' => 'Invalid']));

        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertSame(422, $response->getStatusCode());
        self::assertSame(
            '{"error":{"code":"VALIDATION_FAILED","details":{"email":"Invalid"}}}',
            $response->getContent()
        );
    }

    #[Test]
    public function it_maps_not_found_to_404_payload(): void
    {
        $subscriber = new ApiExceptionSubscriber(new NullLogger(), false);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event  = new ExceptionEvent($kernel, new Request(), Kernel::MAIN_REQUEST, new NotFoundHttpException());

        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('"code":"NOT_FOUND"', (string) $response->getContent());
    }
}
