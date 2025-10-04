<?php
declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\Admin\StudentAdminController;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Mapper\Response\Contracts\StudentClassroomResponsePort;
use App\Service\Contracts\EnrollmentPort;
use App\Service\UserManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;

final class StudentAdminControllerTest extends TestCase
{
    #[Test]
    public function lists_active_classrooms_for_student(): void
    {
        $users       = $this->createMock(UserManager::class);
        $enrollments = $this->createMock(EnrollmentPort::class);                 // <-- correct type
        $mapper      = $this->createMock(StudentClassroomResponsePort::class);   // <-- mock interface, not final class

        $student = new User();
        $student->setRole(UserRoleEnum::STUDENT); // enum-typed property must be initialized

        $users->method('getUserById')->with(42)->willReturn($student);
        $enrollments->method('getActiveForStudent')->with($student)->willReturn([]); // name below
        $mapper->method('toCollection')->with([])->willReturn([]);

        $controller = new StudentAdminController($users, $enrollments, $mapper);
        $controller->setContainer(new Container()); // needed for AbstractController::json()

        // Call the real action name in your controller:
        // If your method is listActiveClassroomsForStudent(int $id): JsonResponse
        $resp = $controller->classroomsForStudent(42);
        self::assertSame(Response::HTTP_OK, $resp->getStatusCode());
    }
}
