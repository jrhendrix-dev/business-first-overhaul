<?php

namespace App\Service;

use App\Entity\Classroom;
use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ClassroomRepository;
use LogicException;
use App\Service\Contracts\EnrollmentPort;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Service responsible for managing business logic related to Classroom entities.
 * Provides methods for classroom creation, assignment, unassignment, and retrieval operations.
 */
class ClassroomManager
{
    /**
     * ClassroomManager constructor.
     *
     * @param EntityManagerInterface $em The Doctrine entity manager for database operations
     * @param ClassroomRepository $classroomRepository Repository for classroom data access
     */
    public function __construct(
        private EntityManagerInterface $em,
        private ClassroomRepository $classroomRepository,
        private EnrollmentPort $enrollments,
    ) {}

    public function normalizeName(string $name): string
    {
        // trim + collapse internal whitespace
        return preg_replace('/\s+/u', ' ', trim($name));
    }

    /**
     * Assigns a teacher to a classroom after validating the role.
     *
     * @param Classroom $classroom The classroom to assign the teacher to
     * @param User $teacher The user to assign as teacher
     *
     * @throws LogicException If the user is not a teacher
     * @note Classroom must be managed by the entity manager
     */
    public function assignTeacher(Classroom $classroom, User $teacher): void
    {
        if (!$teacher->isTeacher()) {
            throw new LogicException('Only teachers can be assigned to a classroom as a teacher');
        }

        // Compare object identity; IDs may be null in unit tests (I used to compare id, and it was failing tests)
        if ($classroom->getTeacher() !== $teacher) {
            $classroom->setTeacher($teacher);
            $this->em->persist($classroom);
            $this->em->flush();
        }
    }

    /**
     * Unassigns all teachers and students from a classroom.
     *
     * @param Classroom $classroom The classroom to reset
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
     * Retrieves a classroom by its ID.
     *
     * @param int $id The classroom's unique identifier
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
     * Creates a new classroom with the specified name.
     *
     * @param string $rawName The name of the classroom
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
            throw new \DomainException('A classroom with that name already exists.');
        }

        $c = new Classroom();
        $c->setName($name);

        try {
            $this->em->persist($c);
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            // race-condition fallback -> surface as a friendly duplicate
            throw new \DomainException('A classroom with that name already exists.');
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
            throw new \DomainException('A classroom with that name already exists.');
        }

        $classroom->setName($name);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new \DomainException('A classroom with that name already exists.');
        }

        return $classroom;
    }

    /**
     * Deletes a classroom from the database.
     *
     * @param Classroom $classroom The classroom to delete
     * @note This will also remove all associations with students and teachers
     */
    public function removeClassroom(Classroom $classroom): void
    {
        $this->em->remove($classroom);
        $this->em->flush();
    }

    /**
     * Soft-drop the ACTIVE enrollment for $student in the given $classroom.
     *
     * Delegates to EnrollmentManager and bubbles its NotFoundHttpException when:
     *  - no ACTIVE enrollment exists for this (student, classroom) pair
     *
     * @throws NotFoundHttpException when no active enrollment is found
     */
    public function removeStudentFromClassroom(User $student, Classroom $classroom): void
    {
        // Single line delegation keeps classroom-facing controllers tidy,
        // while the enrollment rules live in EnrollmentManager.
        $this->enrollments->dropActiveForStudent($student, $classroom);
    }
}
