<?php
declare(strict_types=1);

namespace App\Tests\Functional\Auth;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use PHPUnit\Framework\Attributes\Test;

final class LoginTest extends WebTestCase
{
    #[Test]
    public function login_returns_401_for_invalid_credentials(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
        ]));

        self::assertResponseStatusCodeSame(401);
    }
}
