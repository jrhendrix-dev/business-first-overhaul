<?php
declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\Classroom;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Enum\EnrollmentStatusEnum;
use App\Enum\UserRoleEnum;
use App\Repository\EnrollmentRepository;
use App\Tests\Support\DatabaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(\App\Repository\EnrollmentRepository::class)]
final class EnrollmentRepositoryTest extends DatabaseTestCase
{
    private EnrollmentRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = self::getContainer()->get(EnrollmentRepository::class);
    }

    #[Test]
    public function it_finds_all_and_active_by_classroom_id(): void
    {
        $teacher = $this->makeUser('t1', UserRoleEnum::TEACHER, 't1@example.test');
        $student = $this->makeUser('s1', UserRoleEnum::STUDENT, 's1@example.test');
        $class   = (new Classroom())->setName('B1')->setTeacher($teacher);

        $this->em()->persist($teacher);
        $this->em()->persist($student);
        $this->em()->persist($class);

        $enr = (new Enrollment())
            ->setStudent($student)
            ->setClassroom($class)
            ->setStatus(EnrollmentStatusEnum::ACTIVE);

        $this->em()->persist($enr);
        $this->em()->flush();

        $classId = (int) $class->getId();

        $all    = $this->repo->findAllByClassroomId($classId);
        $active = $this->repo->findActiveByClassroomId($classId);

        self::assertNotEmpty($all);
        self::assertNotEmpty($active);
        self::assertSame('ACTIVE', $active[0]->getStatus()->name);
    }

    #[Test]
    public function find_active_by_student_returns_only_active_sorted_by_name(): void
    {
        $student = $this->makeUser('stud', UserRoleEnum::STUDENT, 'stud@example.test');
        $alpha   = (new Classroom())->setName('Algebra');
        $beta    = (new Classroom())->setName('Biology');
        $gamma   = (new Classroom())->setName('Chemistry');

        $this->em()->persist($student);
        $this->em()->persist($alpha);
        $this->em()->persist($beta);
        $this->em()->persist($gamma);

        $this->em()->persist((new Enrollment())->setStudent($student)->setClassroom($beta)->setStatus(EnrollmentStatusEnum::ACTIVE));
        $this->em()->persist((new Enrollment())->setStudent($student)->setClassroom($alpha)->setStatus(EnrollmentStatusEnum::ACTIVE));
        $this->em()->persist((new Enrollment())->setStudent($student)->setClassroom($gamma)->setStatus(EnrollmentStatusEnum::DROPPED));
        $this->em()->flush();

        $results = $this->repo->findActiveByStudent($student);
        self::assertCount(2, $results);
        self::assertSame('Algebra', $results[0]->getClassroom()->getName());
        self::assertSame('Biology', $results[1]->getClassroom()->getName());
    }

    #[Test]
    public function count_active_by_classroom_counts_only_active(): void
    {
        $class = (new Classroom())->setName('Philosophy');
        $s1 = $this->makeUser('s1x', UserRoleEnum::STUDENT, 's1x@example.test');
        $s2 = $this->makeUser('s2x', UserRoleEnum::STUDENT, 's2x@example.test');

        $this->em()->persist($class);
        $this->em()->persist($s1);
        $this->em()->persist($s2);

        $this->em()->persist((new Enrollment())->setStudent($s1)->setClassroom($class)->setStatus(EnrollmentStatusEnum::ACTIVE));
        $this->em()->persist((new Enrollment())->setStudent($s2)->setClassroom($class)->setStatus(EnrollmentStatusEnum::DROPPED));
        $this->em()->flush();

        self::assertSame(1, $this->repo->countActiveByClassroom($class));
    }

    // ---- helpers ---------------------------------------------------------

    private function makeUser(string $username, UserRoleEnum $role, string $email): User
    {
        $u = (new User())
            ->setUserName($username)
            ->setFirstName(\ucfirst($username))
            ->setLastName('Test')
            ->setEmail($email)
            ->setPassword('hash')
            ->setRole($role);

        $this->em()->persist($u);
        return $u;
    }
}
