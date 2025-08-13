<?php

namespace App\Tests;

use App\Entity\User;
use App\Enum\UserRoleEnum;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class UserControllerTest extends WebTestCase
{
    private function persistUser(array $overrides = []): User
    {
        self::bootKernel();
        $em = self::getContainer()->get('doctrine')->getManager();

        $u = new User();
        $u->setFirstName('Test');
        $u->setLastName('User');
        $u->setEmail($overrides['email'] ?? 'test@example.com');
        $u->setUsername($overrides['username'] ?? 'testuser');
        $u->setPassword('ignored-for-token');
        $u->setRole($overrides['role'] ?? UserRoleEnum::ADMIN);

        $em->persist($u);
        $em->flush();

        return $u;
    }

    private function makeToken(User $user): string
    {
        self::bootKernel();
        $jwt = self::getContainer()->get(\Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface::class);
        return $jwt->create($user);
    }

    public function testListUsers(): void
    {
        $client = static::createClient();
        $user   = $this->persistUser();
        $token  = $this->makeToken($user);

        $client->request(
            'GET',
            '/api/users',
            server: [
                'HTTP_AUTHORIZATION' => "Bearer {$token}",
                'HTTP_ACCEPT'        => 'application/json',
            ]
        );

        self::assertResponseIsSuccessful(); // 200
        self::assertJson($client->getResponse()->getContent());
    }

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

}

