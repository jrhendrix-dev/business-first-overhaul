<?php
// tests/Unit/Entity/EnrollmentInvariantTest.php
declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Enrollment;
use App\Entity\User;
use App\Entity\Classroom;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnrollmentInvariantTest extends TestCase
{
    #[Test]
    public function enrollment_always_has_student_and_classroom(): void
    {
        $e = (new Enrollment())
            ->setStudent(new User())
            ->setClassroom(new Classroom());

        self::assertInstanceOf(User::class, $e->getStudent());
        self::assertInstanceOf(Classroom::class, $e->getClassroom());
    }
}
