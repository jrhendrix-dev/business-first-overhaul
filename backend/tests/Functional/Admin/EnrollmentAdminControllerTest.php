<?php
// tests/Functional/Admin/EnrollmentAdminControllerTest.php
declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use PHPUnit\Framework\Attributes\Test;

final class EnrollmentAdminControllerTest extends WebTestCase
{
    #[Test]
    public function admin_list_returns_array(): void
    {
        $client = static::createClient();

        // Add your admin Bearer token if firewall requires it:
        // $client->setServerParameter('HTTP_Authorization', 'Bearer '.$this->getAdminJwt());

        $client->request('GET', '/api/admin/classes/1/enrollments');

        self::assertResponseIsSuccessful();
        $json = json_decode($client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($json);
        if ($json !== []) {
            self::assertArrayHasKey('id', $json[0]);
            self::assertArrayHasKey('student', $json[0]);
            self::assertArrayHasKey('status', $json[0]);
        }
    }
}
