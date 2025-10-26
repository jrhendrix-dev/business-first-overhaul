<?php

namespace App\Service;

use App\Entity\Classroom;
use App\Entity\User;
use App\Enum\ClassroomStatusEnum;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ClassroomRepository;
use LogicException;
use App\Service\Contracts\EnrollmentPort;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Service responsible for managing business logic related to Classroom entities.
 * Provides methods for classrooms creation, assignment, unassignment, and retrieval operations.
 */
class ClassroomManager
{
    /**
     * ClassroomManager constructor.
     *
     * @param EntityManagerInterface $em The Doctrine entity manager for database operations
     * @param ClassroomRepository $classroomRepository Repository for classrooms data access
     */
    public function __construct(
        private EntityManagerInterface $em,
        private ClassroomRepository    $classroomRepository,
        private EnrollmentManager      $enrollments,
    ) {}

    public function normalizeName(string $name): string
    {
        // trim + collapse internal whitespace
        return preg_replace('/\s+/u', ' ', trim($name));
    }

    /**
     * Assigns a teacher to a classrooms after validating the role.
     *
     * @param Classroom $classroom The classrooms to assign the teacher to
     * @param User $teacher The user to assign as teacher
     *
     * @throws LogicException If the user is not a teacher
     * @note Classroom must be managed by the entity manager
     */
    public function assignTeacher(Classroom $classroom, User $teacher): void
    {
        if ($classroom->isDropped()) {
            throw new LogicException('Cannot assign a teacher to a dropped classrooms.');
        }
        if (!$teacher->isTeacher()) {
            throw new LogicException('Only teachers can be assigned to a classrooms as a teacher');
        }

        // Compare object identity; IDs may be null in unit tests (I used to compare id, and it was failing tests)
        if ($classroom->getTeacher() !== $teacher) {
            $classroom->setTeacher($teacher);
            $this->em->persist($classroom);
            $this->em->flush();
        }
    }

    /**
     * Unassigns all teachers and students from a classrooms.
     *
     * @param Classroom $classroom The classrooms to reset
     * @note Uses toArray() to avoid modifying collection during iteration
     */
    public function unassignAll(Classroom $classroom): void
    {
        $classroom->setTeacher(null);
        $this->em->persist($classroom);

        // Delegate to enrollment layer — no direct student collection on Classroom anymore
        $this->enrollments->dropAllActiveForClassroom($classroom);

        $this->em->flush();
    }

    public function unassignTeacher(Classroom $classroom): void
    {
        $classroom->setTeacher(null);
        $this->em->persist($classroom);
        $this->em->flush();
    }

    /**
     * Retrieves all classrooms from the database.
     *
     * @return Classroom[] Array of Classroom entities
     */
    public function findAll(): array
    {
        return $this->classroomRepository->findAll();
    }

    public function getStudents(int $id): array
    {
        $class= $this->getClassById($id);
        if(!$class){
            throw new LogicException('class not found');
        }
        return $class->getStudents();
    }

    /**
     * Retrieves classrooms by name search.
     *
     * @param string $name The name to search for
     * @return Classroom[] Array of matching Classroom entities
     */
    public function getClassByName(string $name): array
    {
        return $this->classroomRepository->searchByName($name);
    }

    /**
     * Retrieves a classrooms by its ID.
     *
     * @param int $id The classrooms's unique identifier
     * @return Classroom|null The found Classroom entity or null if not found
     */
    public function getClassById(int $id): ?Classroom
    {
        return $this->classroomRepository->find($id);
    }

    /**
     * Retrieves classrooms by teacher ID.
     *
     * @param int $id The teacher's ID to search for
     * @return Classroom[]|null Array of classrooms or null if none found
     */
    public function getFindByTeacher(int $id): ?array
    {
        return $this->classroomRepository->findByTeacher($id);
    }

    /**
     * Retrieves classrooms by student ID.
     *
     * @param int $id The student's ID to search for
     * @return Classroom[] Array of classrooms associated with the student
     */
    public function getFindByStudent(int $id): array
    {
        return $this->classroomRepository->findByStudent($id);
    }

    /**
     * Counts classrooms by teacher ID.
     *
     * @param int $id The teacher's ID to count for
     * @return int Number of classrooms associated with the teacher
     */
    public function getCountByTeacher(int $id): int
    {
        return $this->classroomRepository->countByTeacher($id);
    }

    /**
     * Retrieves all classrooms without an assigned teacher.
     *
     * @return Classroom[] List of unassigned classrooms
     */
    public function getUnassignedClassrooms(): array
    {
        return $this->classroomRepository->findUnassigned();
    }


    /**
     * Creates a new classrooms with the specified name.
     *
     * @param string $rawName The name of the classrooms
     * @return Classroom The newly created and persisted Classroom entity
     */
    public function createClassroom(string $rawName): Classroom
    {
        $name = $this->normalizeName($rawName);
        if ($name === '') {
            throw new \DomainException('Field "name" is required.');
        }

        // app-level duplicate guard
        if ($this->classroomRepository->findOneBy(['name' => $name])) {
            throw new \DomainException('A classrooms with that name already exists.');
        }

        $c = new Classroom();
        $c->setName($name);
        $c->setStatus(ClassroomStatusEnum::ACTIVE);

        try {
            $this->em->persist($c);
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            // race-condition fallback -> surface as a friendly duplicate
            throw new \DomainException('A classrooms with that name already exists.');
        }

        return $c;
    }

    public function rename(Classroom $classroom, string $rawName): Classroom
    {
        $name = $this->normalizeName($rawName);
        if ($name === '') {
            throw new \DomainException('Field "name" is required.');
        }

        // Skip check if unchanged
        if ($name !== $classroom->getName() && $this->classroomRepository->findOneBy(['name' => $name])) {
            throw new \DomainException('A classrooms with that name already exists.');
        }

        $classroom->setName($name);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new \DomainException('A classrooms with that name already exists.');
        }

        return $classroom;
    }

    /**
     * Delete a classrooms or soft-delete when active enrollments exist.
     *
     * @param Classroom $classroom The classrooms to remove.
     * @return bool True when the classrooms was hard-deleted, false when marked DROPPED.
     */
    public function removeClassroom(Classroom $classroom): bool
    {
        $activeEnrollments = $this->enrollments->countActiveByClassroom($classroom);
        if ($activeEnrollments > 0) {
            $classroom->setStatus(ClassroomStatusEnum::DROPPED);
            $classroom->setTeacher(null);
            $this->em->persist($classroom);
            $this->enrollments->dropAllActiveForClassroom($classroom);
            $this->em->flush();

            return false;
        }

        $this->em->remove($classroom);
        $this->em->flush();

        return true;
    }

    /**
     * Soft-drop the ACTIVE enrollment for $student in the given $classrooms.
     *
     * Delegates to EnrollmentManager and bubbles its NotFoundHttpException when:
     *  - no ACTIVE enrollment exists for this (student, classrooms) pair
     *
     * @throws NotFoundHttpException when no active enrollment is found
     */
    public function removeStudentFromClassroom(User $student, Classroom $classroom): void
    {
        // Single line delegation keeps classrooms-facing controllers tidy,
        // while the enrollment rules live in EnrollmentManager.
        $this->enrollments->dropActiveForStudent($student, $classroom);
    }

    /**
     * Detach a student from every classrooms by soft-dropping all ACTIVE enrollments.
     *
     * Idempotent: safe to call repeatedly.
     *
     * @param User $student The user assumed to be in STUDENT role (but not required).
     */
    public function detachStudentFromAnyClassroom(User $student): void
    {
        // Delegate to the enrollment boundary which enforces the "ACTIVE only" rule.
        $this->enrollments->dropAllActiveForStudent($student);
        // No flush here — caller controls transaction boundaries.
    }

    public function reactivate(Classroom $classroom): void
    {
        $classroom->setStatus(ClassroomStatusEnum::ACTIVE);
        $this->em->persist($classroom);
        $this->em->flush();
    }

    /**
     * Unassign a teacher from all classrooms where they are currently assigned.
     *
     * Idempotent: if already unassigned, no changes are made.
     *
     * @param User $teacher The user being unassigned as teacher.
     */
    public function unassignTeacherFromAll(User $teacher): void
    {
        /** @var Classroom[] $owned */
        $owned = $this->classroomRepository->findAllByTeacher($teacher);

        foreach ($owned as $classroom) {
            if ($classroom->getTeacher() === $teacher) {
                $classroom->setTeacher(null);
            }
        }
        // No flush here — caller controls transaction boundaries.
    }


}
