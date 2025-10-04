<?php
// tests/Unit/Controller/StudentAdminControllerTest.php
declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\Admin\StudentAdminController;
use App\Entity\Classroom;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Enum\EnrollmentStatusEnum;
use App\Mapper\Response\StudentClassroomResponseMapper;
use App\Service\Contracts\EnrollmentPort;
use App\Service\UserManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StudentAdminControllerTest extends TestCase
{
    #[Test]
    public function lists_active_classrooms_for_student(): void
    {
        $user = (new User())
            ->setUserName('sara')
            ->setFirstName('Sara')
            ->setLastName('Diaz')
            ->setEmail('sara@example.org')
            ->setPassword('x');

        $users = $this->createMock(UserManager::class);
        $users->method('getUserById')->with(123)->willReturn($user);

        $class = (new Classroom())->setName('B1');
        $enrollment = (new Enrollment())
            ->setStudent($user)
            ->setClassroom($class)
            ->setStatus(EnrollmentStatusEnum::ACTIVE)
            ->setEnrolledAt(new \DateTimeImmutable('2025-01-01T00:00:00Z'));

        $port = $this->createMock(EnrollmentPort::class);
        $port->method('getActiveForStudent')->with($user)->willReturn([$enrollment]);

        $mapper = new StudentClassroomResponseMapper();
        $ctl    = new StudentAdminController($users, $port, $mapper);

        $resp = $ctl->classroomsForStudent(123);

        self::assertSame(200, $resp->getStatusCode());
        self::assertStringContainsString('"status":"ACTIVE"', (string) $resp->getContent());
        self::assertStringContainsString('"className":"B1"', (string) $resp->getContent());
    }
}
