<?php

namespace App\Tests\Service;

use App\Entity\Classroom;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Repository\ClassroomRepository;
use App\Service\ClassroomManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use App\Service\Contracts\EnrollmentPort;

final class ClassroomManagerTest extends TestCase
{
    // --- assignTeacher() ---

    public function test_assignTeacher_throws_forNonTeacherRole(): void
    {
        $em          = $this->createMock(EntityManagerInterface::class);
        $repo        = $this->createMock(ClassroomRepository::class);
        $enrollments = $this->createMock(EnrollmentPort::class);

        $manager = new ClassroomManager($em, $repo, $enrollments);

        $classroom = new Classroom();
        $classroom->setName('A1');

        $notTeacher = new User();
        $notTeacher->setRole(UserRoleEnum::STUDENT);

        $this->expectException(\LogicException::class);

        $manager->assignTeacher($classroom, $notTeacher);
    }

    public function test_assignTeacher_setsTeacher_and_persists(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');

        $repo        = $this->createMock(ClassroomRepository::class);
        $enrollments = $this->createMock(EnrollmentPort::class);

        $manager = new ClassroomManager($em, $repo, $enrollments);

        $classroom = new Classroom();
        $classroom->setName('A1');

        $teacher = new User();
        $teacher->setRole(UserRoleEnum::TEACHER);

        $manager->assignTeacher($classroom, $teacher);

        self::assertSame($teacher, $classroom->getTeacher());
    }

    public function test_assignTeacher_same_teacher_is_idempotent_no_flush(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $repo        = $this->createMock(ClassroomRepository::class);
        $enrollments = $this->createMock(EnrollmentPort::class);

        $manager = new ClassroomManager($em, $repo, $enrollments);

        $classroom = new Classroom();
        $classroom->setName('B2');

        $teacher = new User();
        $teacher->setRole(UserRoleEnum::TEACHER);

        // already assigned before calling the manager
        $classroom->setTeacher($teacher);

        $manager->assignTeacher($classroom, $teacher);

        self::assertSame($teacher, $classroom->getTeacher());
    }

    // --- removeStudentFromClassroom() ---

    public function test_removeStudentFromClassroom_delegates_to_enrollments_with_classroom(): void
    {
        $em          = $this->createMock(EntityManagerInterface::class);
        $repo        = $this->createMock(ClassroomRepository::class);
        $enrollments = $this->createMock(EnrollmentPort::class);

        $manager = new ClassroomManager($em, $repo, $enrollments);

        $classroom = new Classroom();
        $classroom->setName('C2');
        $student   = new User();
        $student->setRole(UserRoleEnum::STUDENT);

        // Expect delegation with both student and classroom
        $enrollments->expects($this->once())
            ->method('dropActiveForStudent')
            ->with($student, $classroom);

        // Manager itself does not flush here; enrollment service handles persistence.
        $em->expects($this->never())->method('flush');

        $manager->removeStudentFromClassroom($student, $classroom);
    }

    // --- unassignAll() ---

    public function test_unassignAll_clears_teacher_and_calls_bulk_drop(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $repo        = $this->createMock(ClassroomRepository::class);
        $enrollments = $this->createMock(EnrollmentPort::class);

        $manager = new ClassroomManager($em, $repo, $enrollments);

        $classroom = new Classroom();
        $classroom->setName('R1');

        $teacher = new User();
        $teacher->setRole(UserRoleEnum::TEACHER);
        $classroom->setTeacher($teacher);

        // Best practice: use the bulk API on the enrollment port
        $enrollments->expects($this->once())
            ->method('dropAllActiveForClassroom')
            ->with($classroom);

        $manager->unassignAll($classroom);

        self::assertNull($classroom->getTeacher());
    }

    // --- create/remove/find wrappers (unchanged) ---

    public function test_createClassroom_persists_and_returns_entity(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(Classroom::class));
        $em->expects($this->once())->method('flush');

        $repo        = $this->createMock(ClassroomRepository::class);
        $enrollments = $this->createMock(EnrollmentPort::class);
        $manager     = new ClassroomManager($em, $repo, $enrollments);

        $c = $manager->createClassroom('Z9');

        self::assertSame('Z9', $c->getName());
    }

    public function test_removeClassroom_removes_and_flushes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('remove')->with($this->isInstanceOf(Classroom::class));
        $em->expects($this->once())->method('flush');

        $repo        = $this->createMock(ClassroomRepository::class);
        $enrollments = $this->createMock(EnrollmentPort::class);
        $manager     = new ClassroomManager($em, $repo, $enrollments);

        $c = new Classroom();
        $c->setName('ToDelete');

        $manager->removeClassroom($c);
    }

    public function test_findAll_and_search_methods_delegate_to_repo(): void
    {
        $em          = $this->createMock(EntityManagerInterface::class);
        $repo        = $this->createMock(ClassroomRepository::class);
        $enrollments = $this->createMock(EnrollmentPort::class);
        $manager     = new ClassroomManager($em, $repo, $enrollments);

        $c1 = new Classroom();
        $c1->setName('A');
        $c2 = new Classroom();
        $c2->setName('B');

        $repo->expects($this->once())->method('findAll')->willReturn([$c1, $c2]);
        self::assertSame([$c1, $c2], $manager->findAll());

        $repo->expects($this->once())->method('searchByName')->with('A')->willReturn([$c1]);
        self::assertSame([$c1], $manager->getClassByName('A'));

        $repo->expects($this->once())->method('find')->with(5)->willReturn($c2);
        self::assertSame($c2, $manager->getClassById(5));

        $repo->expects($this->once())->method('findByTeacher')->with(10)->willReturn([$c1]);
        self::assertSame([$c1], $manager->getFindByTeacher(10));

        $repo->expects($this->once())->method('findByStudent')->with(20)->willReturn([$c2]);
        self::assertSame([$c2], $manager->getFindByStudent(20));

        $repo->expects($this->once())->method('countByTeacher')->with(10)->willReturn(3);
        self::assertSame(3, $manager->getCountByTeacher(10));

        $repo->expects($this->once())->method('findUnassigned')->willReturn([$c1]);
        self::assertSame([$c1], $manager->getUnassignedClassrooms());
    }
}
