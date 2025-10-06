<?php
// tests/Unit/Fixtures/UserFixturesTest.php
declare(strict_types=1);

namespace App\Tests\Unit\Fixtures;

use App\DataFixtures\UserFixtures;
use App\Entity\Classroom;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Enum\GradeComponentEnum;
use App\Service\Contracts\EnrollmentPort;
use App\Service\Contracts\GradePort;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixturesTest extends TestCase
{
    #[Test]
    public function load_uses_enrollment_and_grade_managers_for_seed_data(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hash');

        $enrollments = $this->createMock(EnrollmentPort::class);
        $grades      = $this->createMock(GradePort::class);

        $createdEnrollments = [];
        $enrollments->expects($this->exactly(8))
            ->method('enroll')
            ->willReturnCallback(static function (User $student, Classroom $classroom) use (&$createdEnrollments): Enrollment {
                $enrollment = (new Enrollment())
                    ->setStudent($student)
                    ->setClassroom($classroom);
                $createdEnrollments[] = $enrollment;
                return $enrollment;
            });

        // addGrade now expects GradeComponentEnum, not string
        $grades->expects($this->exactly(8))
            ->method('addGrade')
            ->with(
                self::callback(fn(Enrollment $e) => in_array($e, $createdEnrollments, true)),
                self::isInstanceOf(GradeComponentEnum::class),
                self::isType('float'),
                self::equalTo(10.0)
            );

        $om = $this->createMock(ObjectManager::class);
        $om->expects($this->atLeastOnce())->method('persist');
        $om->expects($this->exactly(2))->method('flush');

        $fixtures = new UserFixtures($hasher, $enrollments, $grades);
        $fixtures->load($om);
    }
}
