<?php
declare(strict_types=1);

namespace App\Tests\Functional\Http;

use App\Http\Exception\ValidationException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ApiExceptionSubscriberTest extends WebTestCase
{
    public function test_validation_exception_returns_422(): void
    {
        $client = static::createClient();
        // Hit an endpoint that will throw ValidationException, or do a small test controller in test env
        $client->request('POST', '/api/example', server: ['CONTENT_TYPE' => 'application/json'], content: '{"email":"bad"}');

        self::assertSame(422, $client->getResponse()->getStatusCode());
        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('VALIDATION_FAILED', $payload['error']['code']);
        self::assertIsArray($payload['error']['details']);
    }
}
