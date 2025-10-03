<?php
// tests/Functional/Auth/LoginTest.php
declare(strict_types=1);

namespace App\Tests\Functional\Auth;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use PHPUnit\Framework\Attributes\Test;

final class LoginTest extends WebTestCase
{
    #[Test]
    public function login_returns_token_and_user(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'admin@example.com',
            'password' => 'adminpass',
        ]));

        self::assertResponseIsSuccessful(); // 200
        $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('token', $json);
        self::assertArrayHasKey('user', $json);
    }
}
