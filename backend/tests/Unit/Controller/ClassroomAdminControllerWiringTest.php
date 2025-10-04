<?php
// tests/Unit/Controller/ClassroomAdminControllerWiringTest.php
declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\Admin\ClassroomAdminController;
use App\Entity\Classroom;
use App\Mapper\Response\Contracts\ClassroomResponsePort;
use App\Repository\ClassroomRepository;
use App\Service\ClassroomManager;
use App\Service\Contracts\EnrollmentPort;
use App\Service\UserManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ClassroomAdminControllerWiringTest extends TestCase
{
    #[Test]
    public function get_one_passes_active_count_into_mapper(): void
    {
        $classroomManager = $this->createMock(ClassroomManager::class);
        $userManager      = $this->createStub(UserManager::class);
        $repo             = $this->createStub(ClassroomRepository::class);
        $validator        = $this->createStub(ValidatorInterface::class);

        // Mock *interfaces* (not finals)
        $enrollments      = $this->createMock(EnrollmentPort::class);
        $mapper           = $this->createMock(ClassroomResponsePort::class);

        $classroomId = 123;
        $classroom   = $this->createStub(Classroom::class);
        $activeCount = 7;

        $classroomManager->method('getClassById')
            ->with($classroomId)->willReturn($classroom);

        $enrollments->method('countActiveByClassroom')
            ->with($classroom)->willReturn($activeCount);

        $mapper->expects($this->once())
            ->method('toDetail')
            ->with($classroom, $activeCount)
            ->willReturn(['id' => $classroomId, 'activeCount' => $activeCount]);

        $controller = new ClassroomAdminController(
            $classroomManager,
            $userManager,
            $repo,
            $mapper,
            $validator,
            $enrollments
        );

        $resp = $controller->getOne($classroomId);
        self::assertSame(Response::HTTP_OK, $resp->getStatusCode());
    }
}
