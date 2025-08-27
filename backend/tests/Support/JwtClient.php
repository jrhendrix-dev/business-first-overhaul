<?php
// tests/Support/JwtClient.php
declare(strict_types=1);

namespace App\Tests\Support;

use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Helpers to authenticate requests with a JWT in functional tests.
 *
 * Usage:
 *   $client  = static::createClient();
 *   $headers = $this->jwtHeaders($client, 'admin@example.com', '1234');
 *   $client->request('PUT', '/api/…', server: $headers, content: json_encode([...]));
 */
trait JwtClient
{
    /**
     * Log in against /api/login and return Authorization headers.
     *
     * @param KernelBrowser $client
     * @param string $email
     * @param string $password
     * @return array<string,string>
     * @throws \JsonException
     */
    protected function jwtHeaders(KernelBrowser $client, string $email, string $password): array
    {
        $client->request(
            'POST',
            '/api/login',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'ACCEPT'       => 'application/json',
            ],
            content: json_encode(['email' => $email, 'password' => $password], JSON_THROW_ON_ERROR)
        );

        Assert::assertSame(200, $client->getResponse()->getStatusCode(), 'Login failed in test.');

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        Assert::assertIsArray($data);
        Assert::assertArrayHasKey('token', $data, 'JWT token not found in login response');

        return [
            'HTTP_AUTHORIZATION' => 'Bearer '.$data['token'],
            'CONTENT_TYPE'       => 'application/json',
            'ACCEPT'             => 'application/json',
        ];
    }
}
