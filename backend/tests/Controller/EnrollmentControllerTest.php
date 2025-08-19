<?php
// tests/Controller/EnrollmentControllerTest.php

namespace App\Tests\Controller;

use App\Entity\Classroom;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EnrollmentControllerTest extends WebTestCase
{
    /** @return EntityManagerInterface */
    private function em(): EntityManagerInterface
    {
        self::bootKernel();
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();
        return $em;
    }

    private function makeToken(User $user): string
    {
        self::bootKernel();
        /** @var JWTTokenManagerInterface $jwt */
        $jwt = self::getContainer()->get(JWTTokenManagerInterface::class);
        return $jwt->create($user);
    }

    private function persistUser(array $overrides = []): User
    {
        $u = new User();
        $u->setFirstName($overrides['firstName'] ?? 'T');
        $u->setLastName($overrides['lastName'] ?? 'User');
        $u->setEmail($overrides['email'] ?? ('t' . uniqid('', true) . '@example.com'));
        $u->setUsername($overrides['username'] ?? ('t' . uniqid('', true)));
        $u->setPassword('ignored-in-tests');
        $u->setRole($overrides['role'] ?? UserRoleEnum::STUDENT);

        $em = $this->em();
        $em->persist($u);
        $em->flush();

        return $u;
    }

    private function persistClassroom(string $name = 'A'): Classroom
    {
        $c = new Classroom();
        $c->setName($name);

        $em = $this->em();
        $em->persist($c);
        $em->flush();

        return $c;
    }

    /**
     * End-to-end: enroll then drop.
     * @throws \JsonException
     */
    public function testEnrollAndDropFlow(): void
    {
        $client = static::createClient();

        // Admin & token
        $admin = $this->persistUser([
            'role'     => UserRoleEnum::ADMIN,
            'email'    => 'a'.uniqid('', true).'@example.com',
            'username' => 'a'.uniqid('', true),
        ]);
        $token = $this->makeToken($admin);

        // Set persistent request headers
        $client->setServerParameter('HTTP_AUTHORIZATION', "Bearer {$token}");
        $client->setServerParameter('HTTP_ACCEPT', 'application/json');

        // Student + class to operate on
        $student = $this->persistUser([
            'role'     => UserRoleEnum::STUDENT,
            'email'    => 's'.uniqid('', true).'@example.com',
            'username' => 's'.uniqid('', true),
        ]);
        $class = $this->persistClassroom('Math '.uniqid('', true));
        $path  = sprintf('/api/classes/%d/students/%d', $class->getId(), $student->getId());

        // PUT enroll
        $client->request('PUT', $path);
        self::assertResponseStatusCodeSame(201);

        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($student->getId(), $payload['studentId'] ?? null);
        self::assertSame($class->getId(), $payload['classId'] ?? null);

        // GET list should include the enrollment (route not protected, header still fine)
        $client->request('GET', sprintf('/api/classes/%d/enrollments', $class->getId()));
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('items', $list);
        self::assertNotEmpty($list['items']);

        // DELETE drop
        $client->request('DELETE', $path);
        self::assertResponseStatusCodeSame(204);
    }

    /**
     * Duplicate enroll should yield 409 Conflict.
     * @throws \JsonException
     */
    public function testEnrollDuplicateReturnsConflict(): void
    {
        $client = self::createClient();

        // Admin & token
        $admin = $this->persistUser([
            'role'     => UserRoleEnum::ADMIN,
            'email'    => 'admin' . uniqid('', true) . '@example.com',
            'username' => 'admin' . uniqid('', true),
        ]);
        $token = $this->makeToken($admin);

        // Persistent headers
        $client->setServerParameter('HTTP_AUTHORIZATION', "Bearer {$token}");
        $client->setServerParameter('HTTP_ACCEPT', 'application/json');

        // Student + class
        $student = $this->persistUser([
            'role'     => UserRoleEnum::STUDENT,
            'email'    => 's' . uniqid('', true) . '@example.com',
            'username' => 's' . uniqid('', true),
        ]);
        $class = $this->persistClassroom('Physics ' . uniqid('', true));

        $path = sprintf('/api/classes/%d/students/%d', $class->getId(), $student->getId());

        // First enroll -> 201 Created
        $client->request('PUT', $path);
        self::assertResponseStatusCodeSame(201);

        // Second enroll (duplicate) -> 409 Conflict with {"error": "..."}
        $client->request('PUT', $path);
        self::assertResponseStatusCodeSame(409);

        $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($json);
        self::assertArrayHasKey('error', $json);
        self::assertNotEmpty($json['error']);
    }

    /**
     * Bulk drop all active enrollments in a class.
     */
    public function testBulkDropAllActiveEnrollments(): void
    {
        $client = static::createClient();

        // Admin & token
        $admin = $this->persistUser([
            'role'     => UserRoleEnum::ADMIN,
            'email'    => 'a'.uniqid('', true).'@e.com',
            'username' => 'a'.uniqid('', true),
        ]);
        $token = $this->makeToken($admin);

        // Persistent headers
        $client->setServerParameter('HTTP_AUTHORIZATION', "Bearer {$token}");
        $client->setServerParameter('HTTP_ACCEPT', 'application/json');

        $class = $this->persistClassroom('History '.uniqid('', true));

        // create 2 students and enroll via API
        foreach ([1, 2] as $i) {
            $s = $this->persistUser([
                'role'     => UserRoleEnum::STUDENT,
                'email'    => "s{$i}".uniqid('', true).'@e.com',
                'username' => "s{$i}".uniqid('', true),
            ]);
            $client->request('PUT', sprintf('/api/classes/%d/students/%d', $class->getId(), $s->getId()));
            self::assertResponseStatusCodeSame(201);
        }

        // Bulk drop
        $client->request('DELETE', sprintf('/api/classes/%d/enrollments', $class->getId()));
        self::assertResponseStatusCodeSame(204);
    }

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }
}
