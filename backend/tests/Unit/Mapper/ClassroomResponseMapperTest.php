<?php
// tests/Unit/Mapper/ClassroomResponseMapperTest.php
declare(strict_types=1);

namespace App\Tests\Unit\Mapper;

use App\Entity\Classroom;
use App\Enum\ClassroomStatusEnum;
use App\Mapper\Response\ClassroomResponseMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ClassroomResponseMapperTest extends TestCase
{
    #[Test]
    public function to_item_maps_minimum_fields(): void
    {
        $mapper = new ClassroomResponseMapper();

        $classroom = $this->createConfiguredStub(Classroom::class, [
            'getId'      => 10,
            'getName'    => 'A1',
            'getTeacher' => null,
            'getStatus'  => ClassroomStatusEnum::ACTIVE,
        ]);

        $item = $mapper->toItem($classroom);

        // Expect ARRAY shape, not DTO
        self::assertIsArray($item);
        self::assertSame(10, $item['id']);
        self::assertSame('A1', $item['name']);
        self::assertArrayHasKey('teacher', $item);
        self::assertNull($item['teacher']);
        self::assertSame(ClassroomStatusEnum::ACTIVE->value, $item['status']);
    }

    #[Test]
    public function to_detail_includes_status_and_active_students(): void
    {
        $mapper = new ClassroomResponseMapper();

        $classroom = $this->createConfiguredStub(Classroom::class, [
            'getId'      => 55,
            'getName'    => 'Zeta',
            'getTeacher' => null,
            'getStatus'  => ClassroomStatusEnum::DROPPED,
        ]);

        $detail = $mapper->toDetail($classroom, 3);

        self::assertSame(55, $detail['id']);
        self::assertSame('Zeta', $detail['name']);
        self::assertSame(3, $detail['activeStudents']);
        self::assertSame(ClassroomStatusEnum::DROPPED->value, $detail['status']);
    }
}
