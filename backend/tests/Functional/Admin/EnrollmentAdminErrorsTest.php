<?php
declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Tests\Functional\Support\AuthClientTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class EnrollmentAdminErrorsTest extends WebTestCase
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

    public function test_dropping_nonexistent_enrollment_yields_404_contract(): void
    {
        $this->client->request('DELETE', '/admin/enrollments/999999');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $payload = json_decode($this->client->getResponse()->getContent(), true);
        // If you want the standardized error shape, assert accordingly:
        // self::assertSame(['error' => ['code' => 'NOT_FOUND', 'details' => []]], $payload);
    }
}
