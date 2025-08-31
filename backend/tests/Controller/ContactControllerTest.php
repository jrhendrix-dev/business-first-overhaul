<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ContactControllerTest extends WebTestCase
{
    #[Test]
    public function post_contact_queues_email_and_returns_202(): void
    {
        $resp = $this->post('/api/contact', [
            'name'    => 'Test User',
            'email'   => 'user@example.com',
            'subject' => 'Hello',
            'message' => 'This is a valid message body with enough length.',
            'consent' => true,
            'website' => '', // honeypot empty
        ]);

        self::assertSame(202, $resp->getStatusCode(), (string) $resp->getContent());
        self::assertTrue($this->isJsonResponse($resp), 'Response must be JSON');

        $data = $this->decodeJson($resp);
        self::assertSame('QUEUED', $data['status'] ?? null);
        self::assertArrayHasKey('id', $data);
        self::assertIsString($data['id']);
        // Optional: ensure it's a non-empty opaque id
        self::assertNotSame('', trim($data['id']));
    }

    #[Test]
    public function post_contact_validates_and_returns_422_with_shape(): void
    {
        $resp = $this->post('/api/contact', [
            'name'    => '',
            'email'   => 'not-an-email',
            'subject' => '',
            'message' => 'short', // too short on purpose
            'consent' => null,
        ]);

        self::assertSame(422, $resp->getStatusCode(), (string) $resp->getContent());
        self::assertTrue($this->isJsonResponse($resp), 'Response must be JSON');

        $json = $this->decodeJson($resp);

        self::assertArrayHasKey('error', $json);
        self::assertSame('VALIDATION_FAILED', $json['error']['code'] ?? null);
        self::assertArrayHasKey('details', $json['error']);

        // at least email must be marked invalid, others may also appear
        self::assertArrayHasKey('email', $json['error']['details']);
        self::assertSame('Invalid', $json['error']['details']['email']);
    }

    #[Test]
    public function post_contact_honeypot_returns_202_but_does_not_validate(): void
    {
        // When honeypot is filled, we short-circuit and return 202 without leaking validation details
        $resp = $this->post('/api/contact', [
            'name'    => '',
            'email'   => 'not-an-email',
            'subject' => '',
            'message' => '',
            'consent' => false,
            'website' => 'spammy', // triggers honeypot
        ]);

        self::assertSame(202, $resp->getStatusCode(), (string) $resp->getContent());
        // May return empty body or minimal JSON; we only assert status to avoid giving bots signals.
    }

    /**
     * POSTs JSON to a path with the right headers and returns the Response.
     *
     * @param array<string,mixed> $payload
     */
    private function post(string $path, array $payload): Response
    {
        $client = static::createClient();
        $client->request(
            method: 'POST',
            uri: $path,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR)
        );

        return $client->getResponse();
    }

    /**
     * True if the response declares JSON and body decodes without error.
     */
    private function isJsonResponse(Response $resp): bool
    {
        $ct = $resp->headers->get('Content-Type') ?? '';
        if (stripos($ct, 'application/json') === false) {
            return false;
        }
        try {
            json_decode($resp->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
            return true;
        } catch (\JsonException) {
            return false;
        }
    }

    /**
     * Decode JSON body as array (throws if invalid).
     *
     * @return array<string,mixed>
     */
    private function decodeJson(Response $resp): array
    {
        /** @var array<string,mixed> $decoded */
        $decoded = json_decode($resp->getContent() ?: '[]', true, 512, JSON_THROW_ON_ERROR);
        return $decoded;
    }
}
