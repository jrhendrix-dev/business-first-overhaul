<?php

namespace App\Tests\Service;

use App\Entity\Classroom;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Repository\UserRepository;
use App\Service\UserManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[CoversClass(UserManager::class)]
final class UserManagerTest extends TestCase
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;
    private UserRepository $repo;

    private UserManager $manager;

    protected function setUp(): void
    {
        $this->em     = $this->createMock(EntityManagerInterface::class);
        $this->hasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->repo   = $this->createMock(UserRepository::class);

        $this->manager = new UserManager($this->em, $this->hasher, $this->repo);
    }

    // --------- simple passthrough fetchers ---------

    #[Test]
    public function getAllUsers_calls_repo(): void
    {
        $users = [new User(), new User()];
        $this->repo->expects($this->once())->method('findAll')->willReturn($users);

        self::assertSame($users, $this->manager->getAllUsers());
    }

    #[Test]
    public function getAllStudents_calls_repo(): void
    {
        $students = [new User()];
        $this->repo->expects($this->once())->method('findAllStudents')->willReturn($students);

        self::assertSame($students, $this->manager->getAllStudents());
    }

    #[Test]
    public function getAllTeachers_calls_repo(): void
    {
        $teachers = [new User()];
        $this->repo->expects($this->once())->method('findAllTeachers')->willReturn($teachers);

        self::assertSame($teachers, $this->manager->getAllTeachers());
    }

    #[Test]
    public function getUserById_calls_repo(): void
    {
        $u = new User();
        $this->repo->expects($this->once())->method('findUserById')->with(123)->willReturn($u);

        self::assertSame($u, $this->manager->getUserById(123));
    }

    #[Test]
    public function getUserByEmail_calls_repo(): void
    {
        $u = new User();
        $this->repo->expects($this->once())->method('findByEmail')->with('a@b.com')->willReturn($u);

        self::assertSame($u, $this->manager->getUserByEmail('a@b.com'));
    }

    #[Test]
    public function getUserByName_calls_repo_without_role(): void
    {
        $list = [new User()];
        $this->repo->expects($this->once())
            ->method('searchByName')
            ->with('ann', null)
            ->willReturn($list);

        self::assertSame($list, $this->manager->getUserByName('ann', null));
    }

    #[Test]
    public function getUserByName_calls_repo_with_role(): void
    {
        $list = [new User()];
        $this->repo->expects($this->once())
            ->method('searchByName')
            ->with('ann', UserRoleEnum::TEACHER)
            ->willReturn($list);

        self::assertSame($list, $this->manager->getUserByName('ann', UserRoleEnum::TEACHER));
    }

    #[Test]
    public function getUserInClassroom_calls_repo(): void
    {
        $u = new User();
        $this->repo->expects($this->once())->method('findStudentInClassroom')->with(7, 3)->willReturn($u);

        self::assertSame($u, $this->manager->getUserInClassroom(7, 3));
    }

    #[Test]
    public function getRecentlyRegistered_calls_repo(): void
    {
        $list = [new User()];
        $this->repo->expects($this->once())->method('findRecentlyRegisteredUsers')->with(10)->willReturn($list);

        self::assertSame($list, $this->manager->getRecentlyRegistered(10));
    }

    #[Test]
    public function getStudentsWithoutClassroom_calls_repo(): void
    {
        $list = [new User()];
        $this->repo->expects($this->once())->method('findStudentsWithoutClassroom')->willReturn($list);

        self::assertSame($list, $this->manager->getStudentsWithoutClassroom());
    }

    #[Test]
    public function getTeachersWithoutClassroom_calls_repo(): void
    {
        $list = [new User()];
        $this->repo->expects($this->once())->method('findTeachersWithoutClassroom')->willReturn($list);

        self::assertSame($list, $this->manager->getTeachersWithoutClassroom());
    }

    #[Test]
    public function getCountByRole_calls_repo(): void
    {
        $this->repo->expects($this->once())->method('countByRole')->with(UserRoleEnum::STUDENT)->willReturn(42);

        self::assertSame(42, $this->manager->getCountByRole(UserRoleEnum::STUDENT));
    }

    // --------- mutators & side effects ---------

    #[Test]
    public function changeRole_noop_when_same_role(): void
    {
        $u = new User();
        $u->setRole(UserRoleEnum::TEACHER);

        // flush must NOT be called
        $this->em->expects($this->never())->method('flush');

        $this->manager->changeRole($u, UserRoleEnum::TEACHER);
        self::assertSame(UserRoleEnum::TEACHER, $u->getRole());
    }

    #[Test]
    public function changeRole_from_student_unsets_classroom_and_flushes(): void
    {
        $classroom = new Classroom();
        $u = new User();
        $u->setRole(UserRoleEnum::STUDENT);
        $u->setClassroom($classroom);

        $this->em->expects($this->once())->method('flush');

        $this->manager->changeRole($u, UserRoleEnum::TEACHER);

        self::assertNull($u->getClassroom(), 'student classroom must be cleared');
        self::assertSame(UserRoleEnum::TEACHER, $u->getRole());
    }

    #[Test]
    public function changePassword_persists_and_flushes(): void
    {
        $u = new User();

        $this->em->expects($this->once())->method('persist')->with($u);
        $this->em->expects($this->once())->method('flush');

        $this->manager->changePassword($u, 'HASH');
        self::assertSame('HASH', $u->getPassword());
    }

    #[Test]
    public function changeEmail_persists_and_flushes(): void
    {
        $u = new User();

        $this->em->expects($this->once())->method('persist')->with($u);
        $this->em->expects($this->once())->method('flush');

        $this->manager->changeEmail($u, 'x@y.com');
        self::assertSame('x@y.com', $u->getEmail());
    }

    #[Test]
    public function unassignAllStudentsFromClassroom_bulk_updates_and_clears_em(): void
    {
        $room = new Classroom();

        $this->repo->expects($this->once())->method('unassignAllFromClassroom')->with($room)->willReturn(5);
        $this->em->expects($this->once())->method('clear')->with(User::class);

        self::assertSame(5, $this->manager->unassignAllStudentsFromClassroom($room));
    }

    // --------- createUser() happy path & errors ---------

    #[Test]
    public function createUser_success_hashes_and_persists(): void
    {
        $this->repo->expects($this->once())->method('findByEmail')->with('a@b.com')->willReturn(null);
        $this->repo->expects($this->once())->method('findOneBy')->with(['username' => 'ann'])->willReturn(null);

        $this->hasher->expects($this->once())->method('hashPassword')->with($this->isInstanceOf(User::class), 'P@ss')->willReturn('HASH');

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $this->em->expects($this->once())->method('flush');

        $u = $this->manager->createUser('Ann', 'Lee', 'a@b.com', 'ann', 'P@ss', UserRoleEnum::STUDENT);

        self::assertSame('Ann', $u->getFirstname());
        self::assertSame('Lee', $u->getLastname());
        self::assertSame('a@b.com', $u->getEmail());
        self::assertSame('ann', $u->getUsername());
        self::assertSame(UserRoleEnum::STUDENT, $u->getRole());
        self::assertSame('HASH', $u->getPassword());
    }

    #[Test]
    public function createUser_throws_domain_when_precheck_email_taken(): void
    {
        $this->repo->expects($this->once())->method('findByEmail')->with('a@b.com')->willReturn(new User());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('email_taken');

        $this->manager->createUser('Ann', 'Lee', 'a@b.com', 'ann', 'P@ss', UserRoleEnum::STUDENT);
    }

    #[Test]
    public function createUser_throws_domain_when_precheck_username_taken(): void
    {
        $this->repo->expects($this->once())->method('findByEmail')->with('a@b.com')->willReturn(null);
        $this->repo->expects($this->once())->method('findOneBy')->with(['username' => 'ann'])->willReturn(new User());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('username_taken');

        $this->manager->createUser('Ann', 'Lee', 'a@b.com', 'ann', 'P@ss', UserRoleEnum::STUDENT);
    }

    #[Test]
    public function createUser_maps_unique_violation_to_email_taken_when_email_now_exists(): void
    {
        // prechecks pass
        $this->repo->expects($this->exactly(2))
            ->method('findByEmail')->with('a@b.com')
            ->willReturnOnConsecutiveCalls(null, new User());

        $this->repo->expects($this->once())->method('findOneBy')->with(['username' => 'ann'])->willReturn(null);

        // hashing happens
        $this->hasher->method('hashPassword')->willReturn('HASH');

        // persist OK, flush throws DB unique violation
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $this->em->expects($this->once())->method('flush')->willThrowException(
            $this->createMock(UniqueConstraintViolationException::class)
        );

        // after catch, service checks if email exists -> return email_taken
         $this->repo->expects($this->exactly(2))->method('findByEmail')->with('a@b.com')->willReturnOnConsecutiveCalls(null, new User());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('email_taken');

        $this->manager->createUser('Ann', 'Lee', 'a@b.com', 'ann', 'P@ss', UserRoleEnum::STUDENT);
    }

    #[Test]
    public function createUser_maps_unique_violation_to_username_taken_when_email_still_free(): void
    {
        // prechecks pass
        $this->repo->expects($this->exactly(2))
            ->method('findByEmail')->with('a@b.com')
            ->willReturnOnConsecutiveCalls(null, null);

        $this->repo->expects($this->once())->method('findOneBy')->with(['username' => 'ann'])->willReturn(null);

        $this->hasher->method('hashPassword')->willReturn('HASH');

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $this->em->expects($this->once())->method('flush')->willThrowException(
            $this->createMock(UniqueConstraintViolationException::class)
        );

        // after catch: email still not found -> map to username_taken
        $this->repo->expects($this->exactly(2))
            ->method('findByEmail')->with('a@b.com')
            ->willReturnOnConsecutiveCalls(null, null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('username_taken');

        $this->manager->createUser('Ann', 'Lee', 'a@b.com', 'ann', 'P@ss', UserRoleEnum::STUDENT);
    }

    #[Test]
    public function removeUser_removes_and_flushes(): void
    {
        $u = new User();

        $this->em->expects($this->once())->method('remove')->with($u);
        $this->em->expects($this->once())->method('flush');

        $this->manager->removeUser($u);
    }
}
