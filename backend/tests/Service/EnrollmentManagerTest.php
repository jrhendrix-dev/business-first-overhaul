<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Classroom;
use App\Entity\User;
use App\Enum\EnrollmentStatusEnum;
use App\Enum\UserRoleEnum;
use App\Service\EnrollmentManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(EnrollmentManager::class)]
final class EnrollmentManagerTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private EnrollmentManager $svc;

    protected function setUp(): void
    {
        self::bootKernel();
        $c = self::getContainer();

        /** @var EntityManagerInterface $em */
        $this->em  = $c->get('doctrine')->getManager();
        /** @var EnrollmentManager $svc */
        $this->svc = $c->get(EnrollmentManager::class);

        // If you have a DB purger/trait, call it here to isolate tests.
        // $this->truncateDatabase($this->em);
    }

    public function test_reenroll_from_dropped_reactivates_instead_of_new_row(): void
    {
        $student = $this->makeStudent(
            'stud_reenroll_'.bin2hex(random_bytes(3)),
            'stud_re+'.bin2hex(random_bytes(3)).'@ex.test'
        );
        $class = $this->makeClassroom('Room Reenroll '.bin2hex(random_bytes(2)));

        // 1) First enrollment
        $en1 = $this->svc->enroll($student, $class);
        self::assertSame(EnrollmentStatusEnum::ACTIVE, $en1->getStatus());

        // 2) Soft drop it
        $en1->setStatus(EnrollmentStatusEnum::DROPPED);
        $en1->setDroppedAt(new \DateTimeImmutable());
        $this->em->flush();

        // 3) Enroll again → should REACTIVATE the SAME row
        $en2 = $this->svc->enroll($student, $class);

        self::assertSame($en1->getId(), $en2->getId(), 'Re-enroll should reactivate the same enrollment row, not create a new one.');
        self::assertSame(EnrollmentStatusEnum::ACTIVE, $en2->getStatus());
        self::assertNull($en2->getDroppedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $en2->getEnrolledAt());
    }

    // -----------------
    // helper factories
    // -----------------

    private function makeStudent(string $username, string $email): User
    {
        $u = new User();
        $u->setUsername($username);
        $u->setFirstname('Test');
        $u->setLastname('Student');
        $u->setEmail($email);
        $u->setPassword('x'); // hashed in real code; tests don’t care
        $u->setRole(UserRoleEnum::STUDENT);

        $this->em->persist($u);
        $this->em->flush();

        return $u;
    }

    private function makeClassroom(string $name): Classroom
    {
        $c = new Classroom();
        $c->setName($name);

        $this->em->persist($c);
        $this->em->flush();

        return $c;
    }
}
