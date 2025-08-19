<?php
// tests/Service/GradeManagerTest.php

namespace App\Tests\Service;

use App\Entity\Classroom;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Service\EnrollmentManager;
use App\Service\GradeManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GradeManagerTest extends KernelTestCase
{
    public function testAddGradeValidatesBounds(): void
    {
        self::bootKernel();
        $c  = self::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $c->get('doctrine')->getManager();

        /** @var GradeManager $gradeMgr */
        $gradeMgr = $c->get(GradeManager::class);
        /** @var EnrollmentManager $enrollMgr */
        $enrollMgr = $c->get(EnrollmentManager::class);

        // Student
        $student = new User();
        $student->setFirstName('S');
        $student->setLastName('L1');
        $student->setEmail('s1-'.uniqid('', true).'@example.com');
        $student->setUsername('s1-'.uniqid('', true));
        $student->setPassword('x');
        $student->setRole(UserRoleEnum::STUDENT);

        // Classroom
        $class = new Classroom();
        $class->setName('A');

        $em->persist($student);
        $em->persist($class);
        $em->flush();

        // Enroll
        $enroll = $enrollMgr->enroll(student: $student, classroom: $class);

        // Adding a grade above max should throw
        $this->expectException(\DomainException::class);
        $gradeMgr->addGrade(
            enrollment: $enroll,
            component: 'Final',
            score: 120,   // > max
            maxScore: 100
        );
    }
}
