<?php

namespace App\Tests\Controller;

use App\Entity\Classroom;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ClassroomTeacherControllerTest extends WebTestCase
{
    private function em(): EntityManagerInterface
    {
        self::bootKernel();
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();
        return $em;
    }

    private function jwt(User $u): string
    {
        self::bootKernel();
        /** @var JWTTokenManagerInterface $jwt */
        $jwt = self::getContainer()->get(JWTTokenManagerInterface::class);
        return $jwt->create($u);
    }

    private function user(UserRoleEnum $role, string $prefix = 'u'): User
    {
        $u = new User();
        $u->setFirstName('F'); $u->setLastName('L');
        $u->setEmail($prefix.uniqid('', true).'@e.com');
        $u->setUsername($prefix.uniqid('', true));
        $u->setPassword('x');
        $u->setRole($role);
        $em = $this->em(); $em->persist($u); $em->flush();
        return $u;
    }

    private function classroom(): Classroom
    {
        $c = new Classroom(); $c->setName('Room '.uniqid('', true));
        $em = $this->em(); $em->persist($c); $em->flush();
        return $c;
    }

    /**
     * @throws \JsonException
     */
    public function testAssignAndRemoveTeacher(): void
    {
        $client = self::createClient();


        $admin   = $this->user(UserRoleEnum::ADMIN, 'a');
        $teacher = $this->user(UserRoleEnum::TEACHER, 't');
        $class   = $this->classroom();

        $token = $this->jwt($admin);

        // persist headers on client
        $client->setServerParameter('HTTP_AUTHORIZATION', "Bearer {$token}");
        $client->setServerParameter('HTTP_ACCEPT', 'application/json');
        $client->setServerParameter('CONTENT_TYPE', 'application/json');

        // PUT assign
        $client->request(
            'PUT',
            "/api/classrooms/{$class->getId()}/teacher",
            server: [], // already set on client
            content: json_encode(['teacherId' => $teacher->getId()], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(200);

        // GET teacher (protected → still has Authorization from client)
        $client->request('GET', "/api/classrooms/{$class->getId()}/teacher");
        self::assertResponseIsSuccessful();
        $j = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('teacher', $j);

        // DELETE teacher
        $client->request('DELETE', "/api/classrooms/{$class->getId()}/teacher");
        self::assertResponseStatusCodeSame(200);
    }


    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }
}
