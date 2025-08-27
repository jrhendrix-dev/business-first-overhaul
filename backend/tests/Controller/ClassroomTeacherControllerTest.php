<?php
// tests/Controller/ClassroomTeacherControllerTest.php
declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\ClassroomTeacherController;
use App\Entity\Classroom;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for assigning/removing a teacher to/from a classroom.
 *
 * We mint a JWT directly (bypassing /api/login) to avoid depending on a seeded user.
 */
#[CoversClass(ClassroomTeacherController::class)]
final class ClassroomTeacherControllerTest extends WebTestCase
{
    private function em(): EntityManagerInterface
    {
        self::bootKernel();
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();
        return $em;
    }

    /**
     * Create a signed JWT for a given user.
     */
    private function jwt(User $u): string
    {
        self::bootKernel();
        /** @var JWTTokenManagerInterface $jwt */
        $jwt = self::getContainer()->get(JWTTokenManagerInterface::class);
        return $jwt->create($u);
    }

    /**
     * Persist a minimal User with the given role.
     * Password is irrelevant here because we don't hit /api/login in this test.
     */
    private function makeUser(UserRoleEnum $role, string $prefix = 'u'): User
    {
        $u = new User();
        $u->setFirstName('F');
        $u->setLastName('L');
        $u->setEmail($prefix . uniqid('', true) . '@e.com');
        $u->setUsername($prefix . uniqid('', true));
        $u->setPassword('x');
        $u->setRole($role);

        $em = $this->em();
        $em->persist($u);
        $em->flush();

        return $u;
    }

    /**
     * Persist a minimal Classroom.
     */
    private function makeClassroom(): Classroom
    {
        $c = new Classroom();
        $c->setName('Room ' . uniqid('', true));

        $em = $this->em();
        $em->persist($c);
        $em->flush();

        return $c;
    }

    /**
     * @throws \JsonException
     */
    public function testAssignAndRemoveTeacher(): void
    {
        $client = self::createClient();

        // Create admin (for auth), a teacher, and a classroom
        $admin   = $this->makeUser(UserRoleEnum::ADMIN, 'admin');
        $teacher = $this->makeUser(UserRoleEnum::TEACHER, 'teacher');
        $class   = $this->makeClassroom();

        // Auth via minted JWT (no dependency on /api/login)
        $token = $this->jwt($admin);
        $client->setServerParameter('HTTP_AUTHORIZATION', "Bearer {$token}");
        $client->setServerParameter('HTTP_ACCEPT', 'application/json');
        $client->setServerParameter('CONTENT_TYPE', 'application/json');

        // ASSIGN
        $client->request(
            'PUT',
            "/api/classrooms/{$class->getId()}/teacher",
            content: json_encode(['teacherId' => $teacher->getId()], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(200); // adjust to 204 if your controller returns no body

        // REMOVE
        $client->request(
            'DELETE',
            "/api/classrooms/{$class->getId()}/teacher"
        );
        self::assertResponseStatusCodeSame(204);
    }

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }
}
