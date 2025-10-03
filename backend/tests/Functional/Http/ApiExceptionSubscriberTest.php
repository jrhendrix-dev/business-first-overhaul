<?php
// tests/Functional/Http/ApiExceptionSubscriberTest.php
declare(strict_types=1);

namespace App\Tests\Functional\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ApiExceptionSubscriberTest extends WebTestCase
{
    #[Test]
    public function test_validation_exception_returns_422(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_test/throw-validation');

        self::assertResponseStatusCodeSame(422);
        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(
            ['error' => ['code' => 'VALIDATION_FAILED', 'details' => ['email' => 'Invalid']]],
            $payload
        );
    }
}
