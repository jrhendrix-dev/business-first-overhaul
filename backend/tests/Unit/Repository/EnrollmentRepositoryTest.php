<?php
// tests/Unit/Repository/EnrollmentRepositoryTest.php
declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\Classroom;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Enum\EnrollmentStatusEnum;
use App\Enum\UserRoleEnum;
use App\Repository\EnrollmentRepository;
use App\Tests\Support\TestManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\ORMSetup;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnrollmentRepositoryTest extends TestCase
{
    private EntityManagerInterface $em;
    private EnrollmentRepository $repository;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [dirname(__DIR__, 3) . '/src/Entity'],
            isDevMode: true,
        );

        $this->em = EntityManager::create(
            ['driver' => 'pdo_sqlite', 'memory' => true],
            $config
        );

        $tool = new SchemaTool($this->em);
        $tool->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        $this->repository = new EnrollmentRepository(new TestManagerRegistry($this->em));
    }

    protected function tearDown(): void
    {
        // Close the in-memory connection so the PDO handle is released
        $this->em->clear();
        $this->em->getConnection()->close();
    }

    #[Test]
    public function is_enrolled_checks_active_status(): void
    {
        $student   = $this->createStudent('student-active');
        $classroom = $this->createClassroom('Science');

        $enrollment = (new Enrollment())
            ->setStudent($student)
            ->setClassroom($classroom)
            ->setStatus(EnrollmentStatusEnum::ACTIVE);

        $this->em->persist($student);
        $this->em->persist($classroom);
        $this->em->persist($enrollment);
        $this->em->flush();

        self::assertTrue($this->repository->isEnrolled($student, $classroom));

        $enrollment->setStatus(EnrollmentStatusEnum::DROPPED);
        $this->em->flush();

        self::assertFalse($this->repository->isEnrolled($student, $classroom));
    }

    #[Test]
    public function soft_drop_all_active_by_classroom_updates_rows_and_sets_timestamp(): void
    {
        $classroom = $this->createClassroom('History');
        $other     = $this->createClassroom('Geography');

        $studentA = $this->createStudent('student-a');
        $studentB = $this->createStudent('student-b');
        $studentC = $this->createStudent('student-c');

        $enrollA = $this->createEnrollment($studentA, $classroom, EnrollmentStatusEnum::ACTIVE);
        $enrollB = $this->createEnrollment($studentB, $classroom, EnrollmentStatusEnum::ACTIVE);
        $this->createEnrollment($studentC, $other, EnrollmentStatusEnum::ACTIVE);

        $this->em->flush();

        $affected = $this->repository->softDropAllActiveByClassroom($classroom);
        self::assertSame(2, $affected);

        $this->em->clear();

        $reloadedA = $this->repository->find($enrollA->getId());
        $reloadedB = $this->repository->find($enrollB->getId());

        self::assertSame(EnrollmentStatusEnum::DROPPED, $reloadedA?->getStatus());
        self::assertNotNull($reloadedA?->getDroppedAt());
        self::assertSame(EnrollmentStatusEnum::DROPPED, $reloadedB?->getStatus());
        self::assertNotNull($reloadedB?->getDroppedAt());
    }

    #[Test]
    public function find_active_by_student_returns_sorted_active_enrollments(): void
    {
        $student = $this->createStudent('student-sorted');
        $alpha   = $this->createClassroom('Algebra');
        $beta    = $this->createClassroom('Biology');
        $gamma   = $this->createClassroom('Chemistry');

        $this->createEnrollment($student, $beta, EnrollmentStatusEnum::ACTIVE);
        $this->createEnrollment($student, $alpha, EnrollmentStatusEnum::ACTIVE);
        $this->createEnrollment($student, $gamma, EnrollmentStatusEnum::DROPPED);

        $this->em->flush();

        $results = $this->repository->findActiveByStudent($student);
        self::assertCount(2, $results);
        self::assertSame('Algebra', $results[0]->getClassroom()->getName());
        self::assertSame('Biology', $results[1]->getClassroom()->getName());
    }

    #[Test]
    public function count_active_by_classroom_returns_number_of_active_enrollments(): void
    {
        $classroom = $this->createClassroom('Philosophy');
        $student1  = $this->createStudent('student-1');
        $student2  = $this->createStudent('student-2');

        $this->createEnrollment($student1, $classroom, EnrollmentStatusEnum::ACTIVE);
        $this->createEnrollment($student2, $classroom, EnrollmentStatusEnum::DROPPED);
        $this->em->flush();

        self::assertSame(1, $this->repository->countActiveByClassroom($classroom));
    }

    // ------- helpers -------

    private function createStudent(string $username): User
    {
        $student = (new User())
            ->setUserName($username)
            ->setFirstName('First ' . $username)
            ->setLastName('Last ' . $username)
            ->setEmail($username . '@example.com')
            ->setPassword('hash')
            ->setRole(UserRoleEnum::STUDENT);

        $this->em->persist($student);
        return $student;
    }

    private function createClassroom(string $name): Classroom
    {
        $classroom = (new Classroom())->setName($name);
        $this->em->persist($classroom);
        return $classroom;
    }

    private function createEnrollment(User $student, Classroom $classroom, EnrollmentStatusEnum $status): Enrollment
    {
        $enrollment = (new Enrollment())
            ->setStudent($student)
            ->setClassroom($classroom)
            ->setStatus($status);

        $this->em->persist($enrollment);
        return $enrollment;
    }
}
