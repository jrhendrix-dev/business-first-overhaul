<?php
// tests/Unit/Repository/GradeRepositoryTest.php
declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\Classroom;
use App\Entity\Enrollment;
use App\Entity\Grade;
use App\Entity\User;
use App\Enum\EnrollmentStatusEnum;
use App\Enum\GradeComponentEnum;
use App\Enum\UserRoleEnum;
use App\Repository\GradeRepository;
use App\Tests\Support\TestManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\ORMSetup;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GradeRepositoryTest extends TestCase
{
    private EntityManagerInterface $em;
    private GradeRepository $repository;


    protected function setUp(): void
     {
         $config = ORMSetup::createAttributeMetadataConfiguration(
             [dirname(__DIR__, 3) . '/src/Entity'],
             true
         );
         $conn   = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
         $this->em = new EntityManager($conn, $config);

         $tool = new SchemaTool($this->em);
         $tool->createSchema($this->em->getMetadataFactory()->getAllMetadata());

         $this->repository = new GradeRepository(new TestManagerRegistry($this->em));
     }

    protected function tearDown(): void
    {
        $this->em->close();
    }

    #[Test]
    public function list_by_enrollment_orders_by_graded_at(): void
    {
        $enrollment = $this->persistEnrollment();

        $older = (new Grade())
            ->setEnrollment($enrollment)
            ->setComponent(GradeComponentEnum::QUIZ)
            ->setScore(8.0)
            ->setMaxScore(10.0)
            ->setGradedAt(new \DateTimeImmutable('-2 days'));

        $newer = (new Grade())
            ->setEnrollment($enrollment)
            ->setComponent(GradeComponentEnum::QUIZ)
            ->setScore(9.0)
            ->setMaxScore(10.0)
            ->setGradedAt(new \DateTimeImmutable('-1 day'));

        $this->em->persist($older);
        $this->em->persist($newer);
        $this->em->flush();

        $results = $this->repository->listByEnrollment($enrollment);

        self::assertCount(2, $results);
        // ensure ascending by gradedAt (older first)
        self::assertLessThan(
            $results[1]->getGradedAt()->getTimestamp(),
            $results[0]->getGradedAt()->getTimestamp()
        );
    }

    #[Test]
    public function average_percent_for_returns_weighted_average(): void
    {
        $enrollment = $this->persistEnrollment();

        $gradeA = (new Grade())
            ->setEnrollment($enrollment)
            ->setComponent(GradeComponentEnum::PROJECT)
            ->setScore(18.0)
            ->setMaxScore(20.0);

        $gradeB = (new Grade())
            ->setEnrollment($enrollment)
            ->setComponent(GradeComponentEnum::EXAM)
            ->setScore(45.0)
            ->setMaxScore(50.0);

        $this->em->persist($gradeA);
        $this->em->persist($gradeB);
        $this->em->flush();

        self::assertSame(90.0, $this->repository->averagePercentFor($enrollment));
    }

    private function persistEnrollment(): Enrollment
    {
        $student = (new User())
            ->setUserName('student1')
            ->setFirstName('Student')
            ->setLastName('One')
            ->setEmail('student1@example.com')
            ->setPassword('hash')
            ->setRole(UserRoleEnum::STUDENT);

        $classroom = (new Classroom())->setName('Math 101');

        $enrollment = (new Enrollment())
            ->setStudent($student)
            ->setClassroom($classroom)
            ->setStatus(EnrollmentStatusEnum::ACTIVE);

        $this->em->persist($student);
        $this->em->persist($classroom);
        $this->em->persist($enrollment);
        $this->em->flush();

        return $enrollment;
    }
}
