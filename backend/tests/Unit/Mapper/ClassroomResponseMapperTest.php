<?php
// tests/Unit/Mapper/ClassroomResponseMapperTest.php
declare(strict_types=1);

namespace App\Tests\Unit\Mapper;

use App\Dto\Classroom\ClassroomItemDto;
use App\Mapper\Response\ClassroomResponseMapper;
use App\Tests\Support\EntityFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ClassroomResponseMapperTest extends TestCase
{
    #[Test]
    public function to_item_maps_minimum_fields(): void
    {
        $teacher = EntityFactory::teacher(id: 7);
        $class   = EntityFactory::classroom(id: 2, name: 'B1', teacher: $teacher);

        $dto = (new ClassroomResponseMapper())->toItem($class);

        self::assertInstanceOf(ClassroomItemDto::class, $dto);
        self::assertSame(2, $dto->id);
        self::assertSame('B1', $dto->name);
        self::assertNotNull($dto->teacher);
        self::assertSame(7, $dto->teacher['id']);
        self::assertSame('Ana', $dto->teacher['firstName']);
        self::assertSame('Pérez', $dto->teacher['lastName']);
        self::assertSame('ana@example.com', $dto->teacher['email']);
    }
}
