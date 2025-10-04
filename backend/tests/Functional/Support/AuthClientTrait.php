<?php
declare(strict_types=1);

namespace App\Tests\Functional\Support;

use App\Entity\User;
use App\Enum\UserRoleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

trait AuthClientTrait
{
    private function authAsAdmin(
        KernelBrowser $client,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        JWTTokenManagerInterface $jwtManager,
        string $email = 'admin@example.test',
        string $plainPassword = 'Admin123$'
    ): void {
        /** @var User|null $user */
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setFirstName('Test');
            $user->setLastName('Admin');
            $user->setEmail($email);
            // your entity uses "userName" (camel N)
            if (method_exists($user, 'setUserName')) {
                $user->setUserName('test_admin');
            } elseif (method_exists($user, 'setUsername')) {
                $user->setUsername('test_admin');
            }

            $this->promoteToAdmin($user);
            $user->setPassword($hasher->hashPassword($user, $plainPassword));

            $em->persist($user);
            $em->flush();
        } else {
            // ensure admin role even if the user already existed
            $this->promoteToAdmin($user);
            $em->flush();
        }

        $token = $jwtManager->create($user);
        $client->setServerParameter('HTTP_Authorization', 'Bearer '.$token);
    }

    /**
     * Support both models:
     *  - single string role: setRole('ROLE_ADMIN')
     *  - array roles: setRoles([...]) or addRole('ROLE_ADMIN')
     */
    private function promoteToAdmin(User $user): void
    {
        $admin = UserRoleEnum::ROLE_ADMIN->value ?? 'ROLE_ADMIN';

        if (method_exists($user, 'setRole')) {
            $user->setRole($admin);
            return;
        }
        if (method_exists($user, 'setRoles')) {
            $user->setRoles([$admin]);
            return;
        }
        if (method_exists($user, 'addRole')) {
            $user->addRole($admin);
            return;
        }
        // Fallback: if nothing else exists, try generic "role" property
        if (property_exists($user, 'role')) {
            $user->role = $admin;
        }
    }
}
