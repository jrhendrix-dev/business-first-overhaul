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
            method_exists($user, 'setUserName') ? $user->setUserName('test_admin') : $user->setUsername('test_admin');

            $this->promoteToAdmin($user);
            $user->setPassword($hasher->hashPassword($user, $plainPassword));
            $em->persist($user);
            $em->flush();
        } else {
            $this->promoteToAdmin($user);
            $em->flush();
        }

        $token = $jwtManager->create($user);
        $client->setServerParameter('HTTP_Authorization', 'Bearer '.$token);
    }

    private function promoteToAdmin(User $user): void
    {
        $enumCase   = UserRoleEnum::ADMIN;       // enum case
        $adminValue = $enumCase->value;           // 'ROLE_ADMIN'

        if (method_exists($user, 'setRole')) {    // prefers enum setter
            $user->setRole($enumCase);
            return;
        }
        if (method_exists($user, 'setRoles')) {   // string-array fallback
            $user->setRoles([$adminValue]);
            return;
        }
        if (method_exists($user, 'addRole')) {
            $user->addRole($adminValue);
            return;
        }

        throw new \RuntimeException('Unable to promote user to admin: no suitable setter found on User.');
    }
}
