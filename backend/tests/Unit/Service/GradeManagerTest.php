<?php
// tests/Unit/Service/GradeManagerTest.php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Enrollment;
use App\Entity\Grade;
use App\Enum\GradeComponentEnum;
use App\Repository\ClassroomRepository;
use App\Repository\GradeRepository;
use App\Service\Contracts\EnrollmentPort;
use App\Service\GradeManager;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GradeManagerTest extends TestCase
{
    #[Test]
    public function add_grade_persists_entity_and_flushes(): void
    {
        $em   = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(GradeRepository::class);
        $port = $this->createMock(EnrollmentPort::class);
        $classes = $this->createStub(ClassroomRepository::class);

        $em->expects($this->once())
            ->method('persist')
            ->with(self::callback(static function (Grade $grade): bool {
                return $grade->getComponent() === GradeComponentEnum::QUIZ
                    && $grade->getScore() === 9.5
                    && $grade->getMaxScore() === 10.0;
            }));
        $em->expects($this->once())->method('flush');

        $manager    = new GradeManager($em, $repo, $port, $classes);
        $enrollment = new Enrollment();
        $result     = $manager->addGrade($enrollment, GradeComponentEnum::QUIZ, 9.5, 10.0);

        self::assertSame($enrollment, $result->getEnrollment());
        self::assertSame(GradeComponentEnum::QUIZ, $result->getComponent());
        self::assertSame(9.5, $result->getScore());
        self::assertSame(10.0, $result->getMaxScore());
    }

    #[Test]
    public function add_grade_rejects_non_positive_max_score(): void
    {
        $manager = new GradeManager(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(GradeRepository::class),
            $this->createMock(EnrollmentPort::class),
            $this->createStub(ClassroomRepository::class),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('maxScore must be > 0');

        $manager->addGrade(new Enrollment(), GradeComponentEnum::PROJECT, 5.0, 0.0);
    }

    #[Test]
    public function add_grade_rejects_score_outside_bounds(): void
    {
        $manager = new GradeManager(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(GradeRepository::class),
            $this->createMock(EnrollmentPort::class),
            $this->createStub(ClassroomRepository::class),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('score must be within 0..maxScore');

        $manager->addGrade(new Enrollment(), GradeComponentEnum::EXAM, 15.0, 10.0);
    }

    #[Test]
    public function add_grade_by_ids_fetches_enrollment_through_port(): void
    {
        $em      = $this->createMock(EntityManagerInterface::class);
        $repo    = $this->createMock(GradeRepository::class);
        $port    = $this->createMock(EnrollmentPort::class);
        $classes = $this->createStub(ClassroomRepository::class);

        $enrollment = new Enrollment();
        $port->expects($this->once())
            ->method('getByIdsOrFail')->with(7, 11)->willReturn($enrollment);

        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');

        $manager = new GradeManager($em, $repo, $port, $classes);
        $grade   = $manager->addGradeByIds(7, 11, GradeComponentEnum::HOMEWORK, 8.0, 10.0);

        self::assertSame(GradeComponentEnum::HOMEWORK, $grade->getComponent());
        self::assertSame($enrollment, $grade->getEnrollment());
    }

    #[Test]
    public function list_by_ids_uses_port_and_repository(): void
    {
        $repo    = $this->createMock(GradeRepository::class);
        $port    = $this->createMock(EnrollmentPort::class);
        $em      = $this->createStub(EntityManagerInterface::class);
        $classes = $this->createStub(ClassroomRepository::class);

        $enrollment = new Enrollment();
        $expected   = [];

        $port->expects($this->once())
            ->method('getByIdsOrFail')->with(4, 9)->willReturn($enrollment);
        $repo->expects($this->once())
            ->method('listByEnrollment')->with($enrollment)->willReturn($expected);

        $manager = new GradeManager($em, $repo, $port, $classes);
        self::assertSame($expected, $manager->listByIds(4, 9));
    }

    #[Test]
    public function average_percent_for_ids_uses_port_and_repository(): void
    {
        $repo    = $this->createMock(GradeRepository::class);
        $port    = $this->createMock(EnrollmentPort::class);
        $em      = $this->createStub(EntityManagerInterface::class);
        $classes = $this->createStub(ClassroomRepository::class);

        $enrollment = new Enrollment();
        $port->expects($this->once())
            ->method('getByIdsOrFail')->with(5, 6)->willReturn($enrollment);
        $repo->expects($this->once())
            ->method('averagePercentFor')->with($enrollment)->willReturn(82.5);

        $manager = new GradeManager($em, $repo, $port, $classes);
        self::assertSame(82.5, $manager->averagePercentForIds(5, 6));
    }
}
