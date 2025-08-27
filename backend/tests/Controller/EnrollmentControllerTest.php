<?php
// tests/Controller/EnrollmentControllerTest.php
declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Enum\EnrollmentStatusEnum;
use App\Enum\UserRoleEnum;
use App\Tests\Factory\ClassroomFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Support\JwtClient;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for Enrollment endpoints.
 */
final class EnrollmentControllerTest extends WebTestCase
{
    use JwtClient;

    /**
     * Get the Doctrine EntityManager for tests.
     */
    private function em(): EntityManagerInterface
    {
        self::bootKernel();
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();
        return $em;
    }

    /**
     * Mint a JWT for a given User (bypasses the login route).
     *
     * @param User $u
     * @return string
     */
    private function jwt(User $u): string
    {
        self::bootKernel();
        /** @var JWTTokenManagerInterface $jwt */
        $jwt = self::getContainer()->get(JWTTokenManagerInterface::class);
        return $jwt->create($u);
    }

    /**
     * Accept either 200 OK or 201 Created for new enrollments.
     *
     * @param int    $status
     * @param string $body
     */
    private function assertOkOrCreated(int $status, string $body = ''): void
    {
        self::assertTrue(
            in_array($status, [200, 201], true),
            sprintf("Expected 200 or 201, got %d. Body: %s", $status, $body)
        );
    }

    /**
     * End-to-end: enroll then list and then drop.
     * @throws \JsonException
     */
    public function testEnrollAndDropFlow(): void
    {
        $client = static::createClient();
        $em     = $this->em();

        // Admin & token
        $admin = UserFactory::create($em, UserRoleEnum::ADMIN);
        $token = $this->jwt($admin);
        $client->setServerParameter('HTTP_AUTHORIZATION', "Bearer {$token}");
        $client->setServerParameter('HTTP_ACCEPT', 'application/json');

        // Student + class
        $student = UserFactory::create($em, UserRoleEnum::STUDENT);
        $class   = ClassroomFactory::create($em, 'Math '.bin2hex(random_bytes(3)));
        $path    = sprintf('/api/classes/%d/students/%d', $class->getId(), $student->getId());

        // PUT enroll -> accept 200 or 201
        $client->request('PUT', $path);
        $this->assertOkOrCreated($client->getResponse()->getStatusCode(), $client->getResponse()->getContent());

        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($student->getId(), $payload['studentId'] ?? null);
        self::assertSame($class->getId(), $payload['classId'] ?? null);

        // GET list (allow either plain array or {items:[...]})
        $client->request('GET', sprintf('/api/classes/%d/enrollments', $class->getId()));
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $rows = isset($list['items']) && is_array($list['items']) ? $list['items'] : $list;
        self::assertIsArray($rows);
        self::assertNotEmpty($rows);

        // DELETE drop -> 204
        $client->request('DELETE', $path);
        self::assertResponseStatusCodeSame(204);
    }

    /**
     * Duplicate enroll is idempotent: second PUT returns 200 with the same row ACTIVE.
     * @throws \JsonException
     */
    public function testEnrollDuplicateIsIdempotent_WithFactories(): void
    {
        $client = self::createClient();
        $em     = $this->em();

        $admin  = UserFactory::create($em, UserRoleEnum::ADMIN);
        $token  = $this->jwt($admin);
        $client->setServerParameter('HTTP_AUTHORIZATION', "Bearer {$token}");
        $client->setServerParameter('HTTP_ACCEPT', 'application/json');

        $student = UserFactory::create($em, UserRoleEnum::STUDENT);
        $class   = ClassroomFactory::create($em, 'Physics '.bin2hex(random_bytes(3)));

        $path = sprintf('/api/classes/%d/students/%d', $class->getId(), $student->getId());

        // First enroll -> accept 200 or 201
        $client->request('PUT', $path);
        $this->assertOkOrCreated($client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
        $first = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        // Second enroll -> 200 (idempotent)
        $client->request('PUT', $path);
        self::assertResponseStatusCodeSame(200);
        $second = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame($first['id'], $second['id']);
        self::assertSame('ACTIVE', $second['status']);
    }

    /**
     * Bulk drop all active enrollments in a class.
     */
    public function testBulkDropAllActiveEnrollments(): void
    {
        $client = self::createClient();
        $em     = $this->em();

        $admin = UserFactory::create($em, UserRoleEnum::ADMIN);
        $token = $this->jwt($admin);
        $client->setServerParameter('HTTP_AUTHORIZATION', "Bearer {$token}");
        $client->setServerParameter('HTTP_ACCEPT', 'application/json');

        $class = ClassroomFactory::create($em, 'History '.bin2hex(random_bytes(2)));

        // create 2 students and enroll via API
        for ($i = 0; $i < 2; $i++) {
            $s = UserFactory::create($em, UserRoleEnum::STUDENT);
            $client->request('PUT', sprintf('/api/classes/%d/students/%d', $class->getId(), $s->getId()));
            $this->assertOkOrCreated($client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
        }

        // Bulk drop -> 204
        $client->request('DELETE', sprintf('/api/classes/%d/enrollments', $class->getId()));
        self::assertResponseStatusCodeSame(204);
    }

    /**
     * Re-enrolling after a DROP should reactivate the same row (ACTIVE, 200).
     * @throws \JsonException
     */
    public function testEnrollEndpointReactivatesWhenDropped(): void
    {
        $client = self::createClient();
        $em     = $this->em();

        // Admin + headers
        $admin = UserFactory::create($em, UserRoleEnum::ADMIN);
        $token = $this->jwt($admin);
        $client->setServerParameter('HTTP_AUTHORIZATION', "Bearer {$token}");
        $client->setServerParameter('HTTP_ACCEPT', 'application/json');

        // Student & class
        $student = UserFactory::create($em, UserRoleEnum::STUDENT);
        $class   = ClassroomFactory::create($em, 'Reenroll '.bin2hex(random_bytes(2)));

        $path = sprintf('/api/classes/%d/students/%d', $class->getId(), $student->getId());

        // 1) First enroll -> accept 200 or 201
        $client->request('PUT', $path);
        $this->assertOkOrCreated(
            $client->getResponse()->getStatusCode(),
            $client->getResponse()->getContent()
        );

        // 2) Drop -> 204
        $client->request('DELETE', $path);
        self::assertResponseStatusCodeSame(204);

        // 3) Enroll again -> 200 + ACTIVE (same row)
        $client->request('PUT', $path);
        self::assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('status', $data);

        $expected = defined(EnrollmentStatusEnum::class.'::ACTIVE')
            ? EnrollmentStatusEnum::ACTIVE->value
            : 'ACTIVE';
        self::assertSame($expected, $data['status']);
    }

    /**
     * Idempotent duplicate enrollment using factory users; no hard-coded IDs or creds.
     * @throws \JsonException
     */
    public function testEnrollDuplicateIsIdempotent(): void
    {
        $client = self::createClient();
        $em     = $this->em();

        // Admin via factory + JWT (no login route dependency)
        $admin = UserFactory::create($em, UserRoleEnum::ADMIN);
        $token = $this->jwt($admin);
        $client->setServerParameter('HTTP_AUTHORIZATION', "Bearer {$token}");
        $client->setServerParameter('HTTP_ACCEPT', 'application/json');

        $student = UserFactory::create($em, UserRoleEnum::STUDENT);
        $class   = ClassroomFactory::create($em, 'Chem '.bin2hex(random_bytes(2)));
        $path    = sprintf('/api/classes/%d/students/%d', $class->getId(), $student->getId());

        // First enroll
        $client->request('PUT', $path);
        self::assertResponseStatusCodeSame(200);
        $first = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        // Second enroll (idempotent)
        $client->request('PUT', $path);
        self::assertResponseStatusCodeSame(200);
        $second = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame($first['id'], $second['id']);
        self::assertSame('ACTIVE', $second['status']);
    }

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }
}
