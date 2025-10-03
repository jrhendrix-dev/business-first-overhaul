<?php
// tests/Functional/Admin/EnrollmentAdminErrorsTest.php
declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use PHPUnit\Framework\Attributes\Test;

final class EnrollmentAdminErrorsTest extends WebTestCase
{
    #[Test]
    public function dropping_nonexistent_enrollment_yields_404_contract(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/api/admin/classes/999/students/999');

        self::assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        // Align this with your standardized error body:
        self::assertArrayHasKey('error', $data);
    }
}
