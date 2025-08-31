<?php

namespace App\Tests\Support;

use App\Entity\User;
use App\Enum\UserRoleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class ApiWebTestCase extends WebTestCase
{
    /**
     * Request auth headers for a throwaway user.
     * Pass e.g. ['ROLE_TEACHER'] or ['ROLE_ADMIN']; defaults to ROLE_USER.
     */
    protected function authHeaders(array $roles = ['ROLE_USER']): array
    {
        self::bootKernel();
        $container = static::getContainer();

        $em     = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);
        $jwt    = $container->get(JWTTokenManagerInterface::class);

        $user = new User();
        // Fill required fields so persistence never fails
        $suffix = bin2hex(random_bytes(4));
        $user
            ->setUsername('tester_'.$suffix)
            ->setFirstname('Test')
            ->setLastname('User')
            ->setEmail("tester+$suffix@example.test");

        // Map requested Symfony role(s) to our enum (single role)
        $this->applyRoles($user, $roles);

        $user->setPassword($hasher->hashPassword($user, 'testpass'));

        $em->persist($user);
        $em->flush();

        $token = $jwt->create($user);

        return [
            'HTTP_Authorization' => 'Bearer '.$token,
            'CONTENT_TYPE'       => 'application/json',
            'ACCEPT'             => 'application/json',
        ];
    }

    /** Map Symfony role names to our single enum role */
    private function applyRoles(User $user, array $roles): void
    {
        if (in_array('ROLE_ADMIN', $roles, true)) {
            $user->setRole(UserRoleEnum::ADMIN);
            return;
        }
        if (in_array('ROLE_TEACHER', $roles, true)) {
            $user->setRole(UserRoleEnum::TEACHER);
            return;
        }
        // default
        $user->setRole(UserRoleEnum::STUDENT);
    }
}
