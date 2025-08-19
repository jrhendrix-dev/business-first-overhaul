<?php

namespace App\Service;

use App\Repository\ClassroomRepository;
use App\Entity\{Enrollment, User, Classroom};
use App\Enum\EnrollmentStatusEnum;
use App\Repository\EnrollmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use DomainException;
use App\Service\Contracts\EnrollmentPort;

/**
 * Application service responsible for Enrollment operations and policies.
 * Supports both soft-drops (status changes) and hard-deletes.
 */
final class EnrollmentManager implements EnrollmentPort
{
    public function __construct(
        private readonly EnrollmentRepository   $enrollments,
        private readonly UserManager            $userManager,
        private readonly ClassroomRepository    $classrooms,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Hard-delete a specific enrollment by student & classroom ids.
     *
     * @throws NotFoundHttpException if the enrollment does not exist
     */
    public function dropByIds(int $studentId, int $classId): void
    {
        $enrollment = $this->enrollments->findOneByStudentIdAndClassId($studentId, $classId)
            ?? throw new NotFoundHttpException('Enrollment not found.');

        $this->drop($enrollment);
    }

    /**
     * Soft-drop the ACTIVE enrollment for a student (optionally filtered by classroom).
     *
     * @throws NotFoundHttpException when no ACTIVE enrollment is found
     */
    public function dropActiveForStudent(User $student, ?Classroom $classroom = null): void
    {
        $en = $this->getActiveEnrollmentForStudent($student, $classroom)
            ?? throw new NotFoundHttpException('Enrollment not found.');

        $en->setStatus(EnrollmentStatusEnum::DROPPED);
        $en->setDroppedAt(new \DateTimeImmutable());
        $this->em->flush();
    }

    /**
     * Soft-drop all ACTIVE enrollments for a student.
     */
    public function dropAllActiveForStudent(User $student): void
    {
        $enrollments = $this->enrollments->findBy([
            'student' => $student,
            'status'  => EnrollmentStatusEnum::ACTIVE,
        ]);

        foreach ($enrollments as $en) {
            $en->setStatus(EnrollmentStatusEnum::DROPPED);
            $en->setDroppedAt(new \DateTimeImmutable());
        }

        $this->em->flush();
    }

    /**
     * Hard-delete the ACTIVE enrollment for a student (optionally filtered by classroom).
     *
     * @throws NotFoundHttpException when none exists
     */
    public function deleteActiveForStudent(User $student, ?Classroom $classroom = null): void
    {
        $en = $this->getActiveEnrollmentForStudent($student, $classroom)
            ?? throw new NotFoundHttpException('Enrollment not found.');

        $this->em->remove($en);
        $this->em->flush();
    }

    /**
     * Core domain operation: enroll a student into a classroom.
     *
     * @throws LogicException   if the User is not a student
     * @throws DomainException  if already enrolled
     */
    /**
     * Core domain operation: enroll a student into a classroom.
     *
     * @throws LogicException   if the User is not a student or is not persisted
     * @throws DomainException  if already enrolled
     */
    public function enroll(User $student, Classroom $classroom): Enrollment
    {
        if (!$student->isStudent()) {
            throw new LogicException('Only students can be assigned to a classroom');
        }

        // Enforce persistence to avoid unintended cascade INSERTs for User/Classroom
        if (null === $student->getId()) {
            throw new LogicException('Student must be persisted before enrollment.');
        }
        if (null === $classroom->getId()) {
            throw new LogicException('Classroom must be persisted before enrollment.');
        }

        // Re-attach as managed references (no SELECT needed)
        /** @var User $managedStudent */
        $managedStudent = $this->em->getReference(User::class, $student->getId());
        /** @var Classroom $managedClassroom */
        $managedClassroom = $this->em->getReference(Classroom::class, $classroom->getId());

        $existing = $this->enrollments->findOneByStudentAndClassroom($managedStudent, $managedClassroom);
        if ($existing !== null) {
            throw new DomainException('Student is already enrolled in this class.');
        }

        $enrollment = new Enrollment();
        $enrollment->setStudent($managedStudent);
        $enrollment->setClassroom($managedClassroom);

        $this->em->persist($enrollment);
        $this->em->flush();

        return $enrollment;
    }

    /**
     * Lookup by enrollment id (repository convenience).
     */
    public function getEnrollmentById(int $id): ?Enrollment
    {
        return $this->enrollments->findEnrollmentById($id);
    }

    /**
     * Convenience: ids → entities → enroll().
     *
     * @throws NotFoundHttpException when student or classroom is missing
     */
    public function enrollByIds(int $studentId, int $classId): Enrollment
    {
        $student = $this->userManager->getUserById($studentId)
            ?? throw new NotFoundHttpException('Student not found.');

        $classroom = $this->classrooms->find($classId)
            ?? throw new NotFoundHttpException('Classroom not found.');

        return $this->enroll($student, $classroom);
    }

    /**
     * Retrieve an existing enrollment by student & classroom ids or fail.
     *
     * @throws NotFoundHttpException on any missing piece
     */
    public function getByIdsOrFail(int $studentId, int $classId): Enrollment
    {
        $student = $this->userManager->getUserById($studentId)
            ?? throw new NotFoundHttpException('User not found.');

        $classroom = $this->classrooms->find($classId)
            ?? throw new NotFoundHttpException('Classroom not found.');

        $enrollment = $this->enrollments->findOneByStudentIdAndClassId($studentId, $classId)
            ?? throw new NotFoundHttpException('Enrollment not found.');

        return $enrollment;
    }

    /**
     * Return the ACTIVE enrollment for a student (optionally for a specific classroom).
     */
    public function getActiveEnrollmentForStudent(User $student, ?Classroom $classroom = null): ?Enrollment
    {
        $criteria = [
            'student' => $student,
            'status'  => EnrollmentStatusEnum::ACTIVE,
        ];
        if ($classroom !== null) {
            $criteria['classroom'] = $classroom;
        }

        return $this->em->getRepository(Enrollment::class)->findOneBy($criteria);
    }

    /**
     * Hard delete an enrollment.
     */
    public function drop(Enrollment $enrollment): void
    {
        $this->em->remove($enrollment);
        $this->em->flush();
    }

    /**
     * Soft-drop all ACTIVE enrollments in a classroom.
     */
    public function dropAllActiveForClassroom(Classroom $classroom): void
    {
        $enrollments = $this->enrollments->findBy([
            'classroom' => $classroom,
            'status'    => EnrollmentStatusEnum::ACTIVE,
        ]);

        foreach ($enrollments as $en) {
            $en->setStatus(EnrollmentStatusEnum::DROPPED);
            $en->setDroppedAt(new \DateTimeImmutable());
        }

        $this->em->flush();
    }
}
