<?php
// tests/Service/EnrollmentManagerTest.php

namespace App\Tests\Service;

use App\Entity\Classroom;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Service\EnrollmentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\Contracts\EnrollmentPort;

final class EnrollmentManagerTest extends KernelTestCase
{
    public function testEnrollPreventsDuplicates(): void
    {
        self::bootKernel();
        $c  = self::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $c->get('doctrine')->getManager();

        /** @var EnrollmentManager $enrollMgr */
        $enrollMgr = $c->get(EnrollmentManager::class);

        // Create student (no chaining: setters return void)
        $student = new User();
        $student->setFirstName('S');
        $student->setLastName('L1');
        $student->setEmail('s1-'.uniqid('', true).'@example.com');
        $student->setUsername('s1-'.uniqid('', true));
        $student->setPassword('x');
        $student->setRole(UserRoleEnum::STUDENT);

        // Create classroom (no chaining)
        $class = new Classroom();
        $class->setName('A');

        $em->persist($student);
        $em->persist($class);
        $em->flush();

        // Enroll once
        $enrollment = $enrollMgr->enroll(student: $student, classroom: $class);
        self::assertNotNull($enrollment);

        // Enrolling the same student in the same class again should throw
        $this->expectException(\DomainException::class);
        $enrollMgr->enroll(student: $student, classroom: $class);
    }
}
