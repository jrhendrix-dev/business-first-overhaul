<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Enrollment;
use App\Entity\Grade;
use App\Repository\GradeRepository;
use App\Service\EnrollmentManager;
use App\Service\GradeManager;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use App\Service\Contracts\EnrollmentPort;

#[CoversClass(GradeManager::class)]
final class GradeManagerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $em;

    /** @var GradeRepository&MockObject */
    private GradeRepository $grades;

    /** @var EnrollmentPort&MockObject */
    private EnrollmentPort $enrollments;

    private GradeManager $sut;

    protected function setUp(): void
    {
        $this->em          = $this->createMock(EntityManagerInterface::class);
        $this->grades      = $this->createMock(GradeRepository::class);
        $this->enrollments = $this->createMock(EnrollmentPort::class);

        $this->sut = new GradeManager($this->em, $this->grades, $this->enrollments);
    }

    #[Test]
    public function addGrade_throws_when_maxScore_not_positive(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('maxScore must be > 0');

        $this->sut->addGrade(new Enrollment(), 'Quiz 1', 5.0, 0.0);
    }

    #[Test]
    public function addGrade_throws_when_score_out_of_range(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('score must be within 0..maxScore');

        $this->sut->addGrade(new Enrollment(), 'Quiz 1', 15.0, 10.0);
    }

    #[Test]
    public function addGrade_persists_and_flushes_with_expected_values(): void
    {
        $enrollment = new Enrollment();

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Grade $g) use ($enrollment) {
                return $g->getEnrollment() === $enrollment
                    && $g->getComponent() === 'Quiz 1'
                    && $g->getScore() === 7.5
                    && $g->getMaxScore() === 10.0;
            }));

        $this->em->expects($this->once())->method('flush');

        $grade = $this->sut->addGrade($enrollment, 'Quiz 1', 7.5, 10.0);

        self::assertInstanceOf(Grade::class, $grade);
        self::assertSame('Quiz 1', $grade->getComponent());
    }

    #[Test]
    public function addGradeByIds_resolves_enrollment_then_delegates(): void
    {
        $enrollment = new Enrollment();
        $this->enrollments->expects($this->once())
            ->method('getByIdsOrFail')
            ->with(123, 456)
            ->willReturn($enrollment);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->sut->addGradeByIds(123, 456, 'Final', 9.0, 10.0);
    }

    #[Test]
    public function listByEnrollment_delegates_to_repository(): void
    {
        $enrollment = new Enrollment();
        $expected   = [new Grade(), new Grade()];

        $this->grades->expects($this->once())
            ->method('listByEnrollment')
            ->with($enrollment)
            ->willReturn($expected);

        self::assertSame($expected, $this->sut->listByEnrollment($enrollment));
    }

    #[Test]
    public function listByIds_fetches_enrollment_then_lists(): void
    {
        $enrollment = new Enrollment();
        $expected   = [new Grade()];

        $this->enrollments->expects($this->once())
            ->method('getByIdsOrFail')
            ->with(1, 2)
            ->willReturn($enrollment);

        $this->grades->expects($this->once())
            ->method('listByEnrollment')
            ->with($enrollment)
            ->willReturn($expected);

        self::assertSame($expected, $this->sut->listByIds(1, 2));
    }

    #[Test]
    public function averagePercentForIds_resolves_enrollment_then_delegates(): void
    {
        $enrollment = new Enrollment();

        $this->enrollments->expects($this->once())
            ->method('getByIdsOrFail')
            ->with(9, 8)
            ->willReturn($enrollment);

        $this->grades->expects($this->once())
            ->method('averagePercentFor')
            ->with($enrollment)
            ->willReturn(82.5);

        self::assertSame(82.5, $this->sut->averagePercentForIds(9, 8));
    }
}
