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

        // seed or ensure a known user exists with password 'Secret123!'
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'ada@maths.org',
            'password' => 'Secret123!',
        ]));

        self::assertResponseStatusCodeSame(200);
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('token', $json);
        self::assertArrayHasKey('user', $json);
        self::assertArrayHasKey('email', $json['user']);
    }
}
