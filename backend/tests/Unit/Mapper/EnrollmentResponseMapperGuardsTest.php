<?php
// tests/Unit/Mapper/EnrollmentResponseMapperGuardsTest.php
declare(strict_types=1);

namespace App\Tests\Unit\Mapper;

use App\Entity\Enrollment;
use App\Mapper\Response\EnrollmentResponseMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnrollmentResponseMapperGuardsTest extends TestCase
{
    #[Test]
    public function it_throws_if_student_missing(): void
    {
        $this->expectException(\LogicException::class);
        $e = new Enrollment(); // no student/classroom set
        (new EnrollmentResponseMapper())->toItem($e);
    }
}
