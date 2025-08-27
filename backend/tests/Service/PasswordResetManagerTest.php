<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Service\PasswordResetManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PasswordResetManagerTest extends KernelTestCase
{
    private function em(): EntityManagerInterface
    {
        self::bootKernel();
        return self::getContainer()->get('doctrine')->getManager();
    }

    public function test_issue_and_consume(): void
    {
        self::bootKernel();
        $em = $this->em();

        $user = new User();
        $user->setFirstName('F'); $user->setLastName('L');
        $user->setEmail('r'.uniqid().'@e.com');
        $user->setUsername('u'.uniqid());
        $user->setPassword('x');
        $user->setRole(UserRoleEnum::STUDENT);
        $em->persist($user); $em->flush();

        /** @var PasswordResetManager $mgr */
        $mgr = self::getContainer()->get(PasswordResetManager::class);

        $plain = $mgr->issue($user, '127.0.0.1');
        self::assertIsString($plain);
        self::assertGreaterThanOrEqual(64, strlen($plain));

        $mgr->consume($user, $plain, 'New-Secret-1');
        // If we call again with the same token it must fail
        $this->expectException(\RuntimeException::class);
        $mgr->consume($user, $plain, 'Other');
    }
}
