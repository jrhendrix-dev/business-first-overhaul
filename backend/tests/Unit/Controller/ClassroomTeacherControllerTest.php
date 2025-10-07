<?php
declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\Api\ClassroomTeacherController;
use App\Entity\Classroom;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Enum\EnrollmentStatusEnum;
use App\Enum\UserRoleEnum;
use App\Repository\ClassroomRepository;
use App\Repository\EnrollmentRepository;
use App\Tests\Support\EntityIdHelper;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Container as SymfonyContainer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class ClassroomTeacherControllerTest extends TestCase
{
    #[Test]
    public function list_classes_returns_teacher_classrooms(): void
    {
        $teacher = (new User())
            ->setUserName('teach')
            ->setFirstName('Taylor')
            ->setLastName('Teacher')
            ->setEmail('teacher@example.com')
            ->setPassword('hash')
            ->setRole(UserRoleEnum::TEACHER);
        EntityIdHelper::setId($teacher, 10);

        $classroom = (new Classroom())
            ->setName('Algebra 1')
            ->setTeacher($teacher);
        EntityIdHelper::setId($classroom, 21);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($teacher);

        $classes = $this->createMock(ClassroomRepository::class);
        $classes->expects($this->once())
            ->method('findBy')
            ->with(['teacher' => $teacher], ['name' => 'ASC'])
            ->willReturn([$classroom]);

        $enrollments = $this->createMock(EnrollmentRepository::class);

        $controller = new ClassroomTeacherController($security, $classes, $enrollments);
        $controller->setContainer(new SymfonyContainer());

        $response = $controller->listClasses();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame([
            ['id' => 21, 'name' => 'Algebra 1'],
        ], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function roster_with_status_all_returns_full_history(): void
    {
        $teacher = (new User())
            ->setUserName('teach')
            ->setFirstName('Taylor')
            ->setLastName('Teacher')
            ->setEmail('teacher@example.com')
            ->setPassword('hash')
            ->setRole(UserRoleEnum::TEACHER);
        EntityIdHelper::setId($teacher, 10);

        $classroom = (new Classroom())
            ->setName('Algebra 1')
            ->setTeacher($teacher);
        EntityIdHelper::setId($classroom, 21);

        $studentActive = (new User())
            ->setUserName('student1')
            ->setFirstName('Amy')
            ->setLastName('Anderson')
            ->setEmail('amy@example.com')
            ->setPassword('hash')
            ->setRole(UserRoleEnum::STUDENT);
        EntityIdHelper::setId($studentActive, 31);

        $studentDropped = (new User())
            ->setUserName('student2')
            ->setFirstName('Brian')
            ->setLastName('Brown')
            ->setEmail('brian@example.com')
            ->setPassword('hash')
            ->setRole(UserRoleEnum::STUDENT);
        EntityIdHelper::setId($studentDropped, 32);

        $activeEnrollment = (new Enrollment())
            ->setStudent($studentActive)
            ->setClassroom($classroom)
            ->setEnrolledAt(new DateTimeImmutable('2024-02-01T09:00:00+00:00'))
            ->setStatus(EnrollmentStatusEnum::ACTIVE);
        EntityIdHelper::setId($activeEnrollment, 41);

        $droppedEnrollment = (new Enrollment())
            ->setStudent($studentDropped)
            ->setClassroom($classroom)
            ->setEnrolledAt(new DateTimeImmutable('2024-01-05T09:00:00+00:00'))
            ->setDroppedAt(new DateTimeImmutable('2024-03-10T09:00:00+00:00'))
            ->setStatus(EnrollmentStatusEnum::DROPPED);
        EntityIdHelper::setId($droppedEnrollment, 42);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($teacher);

        $classes = $this->createMock(ClassroomRepository::class);
        $classes->expects($this->once())->method('find')->with(21)->willReturn($classroom);

        $enrollments = $this->createMock(EnrollmentRepository::class);
        $enrollments->expects($this->never())->method('findActiveByClassroom');
        $enrollments->expects($this->once())
            ->method('findBy')
            ->with(['classroom' => $classroom], ['enrolledAt' => 'ASC'])
            ->willReturn([$activeEnrollment, $droppedEnrollment]);

        $controller = new ClassroomTeacherController($security, $classes, $enrollments);
        $controller->setContainer(new SymfonyContainer());

        $response = $controller->roster(21, new Request(query: ['status' => 'all']));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame([
            [
                'enrollmentId' => 41,
                'status'       => EnrollmentStatusEnum::ACTIVE->value,
                'enrolledAt'   => '2024-02-01T09:00:00+00:00',
                'droppedAt'    => null,
                'student'      => [
                    'id'        => 31,
                    'firstName' => 'Amy',
                    'lastName'  => 'Anderson',
                    'email'     => 'amy@example.com',
                ],
            ],
            [
                'enrollmentId' => 42,
                'status'       => EnrollmentStatusEnum::DROPPED->value,
                'enrolledAt'   => '2024-01-05T09:00:00+00:00',
                'droppedAt'    => '2024-03-10T09:00:00+00:00',
                'student'      => [
                    'id'        => 32,
                    'firstName' => 'Brian',
                    'lastName'  => 'Brown',
                    'email'     => 'brian@example.com',
                ],
            ],
        ], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function roster_denies_access_to_foreign_classroom(): void
    {
        $teacher = (new User())
            ->setUserName('teach')
            ->setFirstName('Taylor')
            ->setLastName('Teacher')
            ->setEmail('teacher@example.com')
            ->setPassword('hash')
            ->setRole(UserRoleEnum::TEACHER);
        EntityIdHelper::setId($teacher, 10);

        $otherTeacher = (new User())
            ->setUserName('other')
            ->setFirstName('Olivia')
            ->setLastName('Other')
            ->setEmail('other@example.com')
            ->setPassword('hash')
            ->setRole(UserRoleEnum::TEACHER);
        EntityIdHelper::setId($otherTeacher, 11);

        $classroom = (new Classroom())
            ->setName('Biology')
            ->setTeacher($otherTeacher);
        EntityIdHelper::setId($classroom, 22);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($teacher);

        $classes = $this->createMock(ClassroomRepository::class);
        $classes->method('find')->with(22)->willReturn($classroom);

        $controller = new ClassroomTeacherController($security, $classes, $this->createMock(EnrollmentRepository::class));
        $controller->setContainer(new SymfonyContainer());

        $this->expectException(AccessDeniedException::class);

        $controller->roster(22, new Request());
    }
}

