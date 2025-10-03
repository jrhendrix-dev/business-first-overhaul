<?php
declare(strict_types=1);

namespace App\Tests\Functional\Support;

use App\Entity\User;
use App\Enum\UserRoleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Helper trait to authenticate the BrowserKit client with a valid Bearer token.
 * Creates (or reuses) an admin user and sets Authorization header.
 */
trait AuthClientTrait
{
    /**
     * Authenticate the client as ROLE_ADMIN and attach a Bearer token.
     *
     * @param KernelBrowser                    $client
     * @param EntityManagerInterface           $em
     * @param UserPasswordHasherInterface      $hasher
     * @param JWTTokenManagerInterface         $jwtManager
     * @param string                           $email
     * @param string                           $plainPassword
     */
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
            $user->setUserName('test_admin');
            $user->setRoles([UserRoleEnum::ROLE_ADMIN->value]); // ensure ROLE_ADMIN
            $user->setPassword($hasher->hashPassword($user, $plainPassword));
            $em->persist($user);
            $em->flush();
        } elseif (!in_array(UserRoleEnum::ROLE_ADMIN->value, $user->getRoles(), true)) {
            $user->setRoles([UserRoleEnum::ROLE_ADMIN->value]);
            $em->flush();
        }

        $token = $jwtManager->create($user);
        $client->setServerParameter('HTTP_Authorization', 'Bearer '.$token);
    }
}
