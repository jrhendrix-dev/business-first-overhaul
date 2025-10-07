<?php
declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Tests\Functional\Support\AuthClientTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class EnrollmentAdminControllerTest extends WebTestCase
{
    use AuthClientTrait;

    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;

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

    /**
     * The list endpoint requires a classroom id. If the classroom doesn't exist,
     * our contract returns a NOT_FOUND envelope.
     */
    public function test_list_with_unknown_class_returns_404_contract(): void
    {
        $router = static::getContainer()->get('router');
        $url = $router->generate('admin_enrollments_list', ['classId' => 999999]);

        $this->client->request('GET', $url);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertArrayHasKey('error', $payload);
        self::assertSame('NOT_FOUND', $payload['error']['code'] ?? null);
    }
}
