<?php
// tests/Controller/GradeControllerTest.php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\GradeController;
use App\Entity\Classroom;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Service\EnrollmentManager;
use App\Tests\Factory\ClassroomFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[CoversClass(GradeController::class)]
final class GradeControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    /** Persisted student used in scenarios. */
    private User $student;

    /** Persisted classroom used in scenarios (avoid KernelTestCase::$class collision). */
    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        /** @var EntityManagerInterface $em */
        $this->em = static::getContainer()->get('doctrine')->getManager();

        // DO NOT TRUNCATE when using DAMA DoctrineTestBundle.
        // Each test runs in a transaction and rolls back automatically.

        // Canonical fixtures with guaranteed-unique username/email
        $this->student   = UserFactory::create($this->em, UserRoleEnum::STUDENT);
        $this->classroom = ClassroomFactory::create($this->em);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
        unset($this->client, $this->em, $this->student, $this->classroom);
    }

    private function jwt(User $u): string
    {
        /** @var JWTTokenManagerInterface $jwt */
        $jwt = static::getContainer()->get(JWTTokenManagerInterface::class);
        return $jwt->create($u);
    }

    /** Create an Enrollment via the domain service (bypassing API). */
    private function enroll(User $student, Classroom $class): Enrollment
    {
        /** @var EnrollmentManager $svc */
        $svc = static::getContainer()->get(EnrollmentManager::class);
        return $svc->enroll($student, $class);
    }

    public function testAddUpdateDeleteGrade(): void
    {
        // Admin & auth headers
        $admin = UserFactory::create($this->em, UserRoleEnum::ADMIN);
        $token = $this->jwt($admin);

        $this->client->setServerParameter('HTTP_AUTHORIZATION', "Bearer {$token}");
        $this->client->setServerParameter('HTTP_ACCEPT', 'application/json');

        // Enroll student
        $enrollment = $this->enroll($this->student, $this->classroom);

        // Add grade
        $this->client->request(
            method: 'POST',
            uri: "/api/enrollments/{$enrollment->getId()}/grades",
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['component' => 'quiz', 'score' => 8.5, 'maxScore' => 10], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(201);

        $created = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('id', $created);
        $gradeId = (int) $created['id'];

        // Update
        $this->client->request(
            method: 'PUT',
            uri: "/api/grades/{$gradeId}",
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['score' => 9.0], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(200);

        // Delete
        $this->client->request('DELETE', "/api/grades/{$gradeId}");
        self::assertResponseStatusCodeSame(204);
    }

    public function testAddGradeValidationFails(): void
    {
        $admin = UserFactory::create($this->em, UserRoleEnum::ADMIN);
        $token = $this->jwt($admin);

        $this->client->setServerParameter('HTTP_AUTHORIZATION', "Bearer {$token}");
        $this->client->setServerParameter('HTTP_ACCEPT', 'application/json');

        $enrollment = $this->enroll($this->student, $this->classroom);

        // Missing/invalid fields -> 400
        $this->client->request(
            method: 'POST',
            uri: "/api/enrollments/{$enrollment->getId()}/grades",
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['component' => '', 'score' => -1, 'maxScore' => 0], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(400);
    }

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }
}
