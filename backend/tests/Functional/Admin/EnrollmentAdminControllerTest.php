<?php
declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Tests\Functional\Support\AuthClientTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EnrollmentAdminControllerTest extends WebTestCase
{
    use AuthClientTrait;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();

        $c = static::getContainer();
        $this->authAsAdmin(
            $this->client,
            $c->get('doctrine.orm.entity_manager'),
            $c->get('security.user_password_hasher'),
            $c->get('lexik_jwt_authentication.jwt_manager'),
        );
    }

    public function test_admin_list_returns_array(): void
    {
        // adjust to your new route prefix if you moved it:
        $this->client->request('GET', '/admin/enrollments'); // "/api" is pre-added by config
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
    }
}
