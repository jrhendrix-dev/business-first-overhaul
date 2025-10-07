<?php
// tests/Unit/Entity/GradeTest.php
declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Enrollment;
use App\Entity\Grade;
use App\Enum\GradeComponentEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Grade::class)]
final class GradeTest extends TestCase
{
    #[Test]
    public function percent_returns_zero_when_max_score_is_zero(): void
    {
        $grade = new Grade();
        $grade->setEnrollment(new Enrollment());
        $grade->setComponent(GradeComponentEnum::QUIZ);
        $grade->setScore(8.0);
        $grade->setMaxScore(0.0);

        self::assertSame(0.0, $grade->getPercent());
    }

    #[Test]
    public function percent_returns_expected_value(): void
    {
        $grade = new Grade();
        $grade->setEnrollment(new Enrollment());
        $grade->setComponent(GradeComponentEnum::EXAM);
        $grade->setScore(45.0);
        $grade->setMaxScore(50.0);

        self::assertSame(90.0, $grade->getPercent());
    }
}
