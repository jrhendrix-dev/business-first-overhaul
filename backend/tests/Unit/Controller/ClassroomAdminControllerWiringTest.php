<?php
// tests/Unit/Controller/ClassroomAdminControllerWiringTest.php
declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\Admin\ClassroomAdminController;
use App\Mapper\Response\ClassroomResponseMapper;
use App\Repository\ClassroomRepository;
use App\Repository\EnrollmentRepository;
use App\Service\ClassroomManager;
use App\Service\UserManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ClassroomAdminControllerWiringTest extends TestCase
{
    #[Test]
    public function get_one_passes_active_count_into_mapper(): void
    {
        $classroom = $this->createMock(\App\Entity\Classroom::class);
        $cm  = $this->createMock(ClassroomManager::class);
        $um  = $this->createMock(UserManager::class);
        $cr  = $this->createMock(ClassroomRepository::class);
        $er  = $this->createMock(EnrollmentRepository::class);
        $map = $this->createMock(ClassroomResponseMapper::class);
        $val = $this->createMock(ValidatorInterface::class);

        $cm->method('getClassById')->willReturn($classroom);
        $er->method('countActiveByClassroom')->with($classroom)->willReturn(2);
        $map->method('toDetail')->willReturn(new \App\Dto\Classroom\ClassroomDetailDto(2, 'B1', null, 2));

        $ctl = new ClassroomAdminController($cm, $um, $cr, $map, $val, $er);
        $resp = $ctl->getOne(2);

        self::assertSame(200, $resp->getStatusCode());
        self::assertStringContainsString('"activeStudents":2', (string) $resp->getContent());
    }
}
