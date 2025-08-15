<?php

namespace App\Tests\Service;

use App\Entity\Classroom;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Repository\ClassroomRepository;
use App\Service\ClassroomManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ClassroomManagerTest extends TestCase
{
    // --- assignTeacher() ---

    public function test_assignTeacher_throws_forNonTeacherRole(): void
    {
        $em   = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(ClassroomRepository::class);
        $manager = new ClassroomManager($em, $repo);

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

        $repo = $this->createMock(ClassroomRepository::class);
        $manager = new ClassroomManager($em, $repo);

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
        // first call (when we initially setTeacher below) is not from manager; we only care that manager doesn't flush again
        $em->expects($this->never())->method('flush');

        $repo = $this->createMock(ClassroomRepository::class);
        $manager = new ClassroomManager($em, $repo);

        $classroom = new Classroom();
        $classroom->setName('B2');

        $teacher = new User();
        $teacher->setRole(UserRoleEnum::TEACHER);

        // Teacher already assigned:
        $classroom->setTeacher($teacher);

        $manager->assignTeacher($classroom, $teacher);

        self::assertSame($teacher, $classroom->getTeacher());
    }

    // --- assignStudent() ---

    public function test_assignStudent_throws_if_not_student(): void
    {
        $em   = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(ClassroomRepository::class);
        $manager = new ClassroomManager($em, $repo);

        $classroom = new Classroom();
        $classroom->setName('C1');

        $notStudent = new User();
        $notStudent->setRole(UserRoleEnum::TEACHER);

        $this->expectException(\LogicException::class);
        $manager->assignStudent($classroom, $notStudent);
    }

    public function test_assignStudent_adds_student_and_flushes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(Classroom::class));
        $em->expects($this->once())->method('flush');

        $repo = $this->createMock(ClassroomRepository::class);
        $manager = new ClassroomManager($em, $repo);

        $classroom = new Classroom();
        $classroom->setName('C2');

        $student = new User();
        $student->setRole(UserRoleEnum::STUDENT);

        $manager->assignStudent($classroom, $student);

        self::assertTrue($classroom->getStudents()->contains($student));
        self::assertSame($classroom, $student->getClassroom());
    }

    // --- unassignAll() ---

    public function test_unassignAll_clears_teacher_and_students_and_flushes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $repo = $this->createMock(ClassroomRepository::class);
        $manager = new ClassroomManager($em, $repo);

        $classroom = new Classroom();
        $classroom->setName('R1');

        $teacher = new User();
        $teacher->setRole(UserRoleEnum::TEACHER);
        $classroom->setTeacher($teacher);

        $s1 = new User();
        $s1->setRole(UserRoleEnum::STUDENT);
        $classroom->addStudent($s1);

        $s2 = new User();
        $s2->setRole(UserRoleEnum::STUDENT);
        $classroom->addStudent($s2);

        $manager->unassignAll($classroom);

        self::assertNull($classroom->getTeacher());
        self::assertCount(0, $classroom->getStudents());
        self::assertNull($s1->getClassroom());
        self::assertNull($s2->getClassroom());
    }

    // --- removeStudentFromClassroom() ---

    public function test_removeStudentFromClassroom_when_assigned_removes_and_flushes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $repo = $this->createMock(ClassroomRepository::class);
        $manager = new ClassroomManager($em, $repo);

        $classroom = new Classroom();
        $classroom->setName('R2');

        $student = new User();
        $student->setRole(UserRoleEnum::STUDENT);
        $classroom->addStudent($student);

        $manager->removeStudentFromClassroom($student);

        self::assertFalse($classroom->getStudents()->contains($student));
        self::assertNull($student->getClassroom());
    }

    public function test_removeStudentFromClassroom_when_not_assigned_just_flushes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $repo = $this->createMock(ClassroomRepository::class);
        $manager = new ClassroomManager($em, $repo);

        $student = new User();
        $student->setRole(UserRoleEnum::STUDENT);

        $manager->removeStudentFromClassroom($student);

        self::assertNull($student->getClassroom());
    }

    // --- create/remove/find wrappers ---

    public function test_createClassroom_persists_and_returns_entity(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(Classroom::class));
        $em->expects($this->once())->method('flush');

        $repo = $this->createMock(ClassroomRepository::class);
        $manager = new ClassroomManager($em, $repo);

        $c = $manager->createClassroom('Z9');

        self::assertSame('Z9', $c->getName());
    }

    public function test_removeClassroom_removes_and_flushes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('remove')->with($this->isInstanceOf(Classroom::class));
        $em->expects($this->once())->method('flush');

        $repo = $this->createMock(ClassroomRepository::class);
        $manager = new ClassroomManager($em, $repo);

        $c = new Classroom();
        $c->setName('ToDelete');

        $manager->removeClassroom($c);
    }

    public function test_findAll_and_search_methods_delegate_to_repo(): void
    {
        $em   = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(ClassroomRepository::class);
        $manager = new ClassroomManager($em, $repo);

        $c1 = new Classroom(); $c1->setName('A');
        $c2 = new Classroom(); $c2->setName('B');

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
