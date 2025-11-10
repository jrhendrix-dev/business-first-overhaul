<?php

namespace App\Service;

use App\Entity\Classroom;
use App\Entity\Exception\ClassroomInactiveException;
use App\Entity\User;
use App\Enum\ClassroomStatusEnum;
use App\Repository\ClassroomRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
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

    /**
     * Normalize a classroom name (trim and collapse inner whitespace).
     *
     * @param string $name
     * @return string
     */
    public function normalizeName(string $name): string
    {
        return preg_replace('/\s+/u', ' ', trim($name));
    }

    /**
     * Assign a teacher to a classroom.
     *
     * @param Classroom $classroom
     * @param User      $teacher
     *
     * @throws ClassroomInactiveException When the classroom is not ACTIVE
     * @throws LogicException             When the user is not a teacher
     */
    public function assignTeacher(Classroom $classroom, User $teacher): void
    {
        $this->assertActive($classroom);

        if (!$teacher->isTeacher()) {
            throw new LogicException('Only teachers can be assigned to a classroom as a teacher');
        }

        // Use object identity to avoid test flakiness with null IDs.
        if ($classroom->getTeacher() !== $teacher) {
            $classroom->setTeacher($teacher);
            $this->em->persist($classroom);
            $this->em->flush();
        }
    }



    /**
     * Ensure a classroom is ACTIVE before write operations.
     *
     * @param Classroom $classroom
     * @throws ClassroomInactiveException
     */
    private function assertActive(Classroom $classroom): void
    {
        if ($classroom->getStatus() !== ClassroomStatusEnum::ACTIVE) {
            // surface the exact status in the exception
            throw new ClassroomInactiveException($classroom->getStatus()->value);
        }
    }


    /**
     * Unassign the teacher and soft-drop all ACTIVE enrollments.
     * Safe to call on DROPPED as well (cleanup).
     *
     * @param Classroom $classroom
     */
    public function unassignAll(Classroom $classroom): void
    {
        $classroom->setTeacher(null);
        $this->em->persist($classroom);

        $this->enrollments->dropAllActiveForClassroom($classroom);

        $this->em->flush();
    }

    /**
     * Unassign the current teacher from a classroom (idempotent).
     *
     * @param Classroom $classroom
     */
    public function unassignTeacher(Classroom $classroom): void
    {
        $classroom->setTeacher(null);
        $this->em->persist($classroom);
        $this->em->flush();
    }

    /**
     * @return Classroom[]
     */
    public function findAll(): array
    {
        return $this->classroomRepository->findAll();
    }

    /**
     * @param int $id
     * @return array<int,User>
     */
    public function getStudents(int $id): array
    {
        $class = $this->getClassById($id);
        if (!$class) {
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

    private function applyPrice(?float $price, ?string $currency, Classroom $c): void
    {
        if ($price !== null) {
            $cents = (int) \round($price * 100);
            if ($cents < 0) {
                throw new \DomainException('price must be >= 0');
            }
            $c->setPriceCents($cents);
        }
        if ($currency !== null && \method_exists($c, 'setCurrency')) {
            $c->setCurrency($currency);
        }
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
     * @param string $rawName
     * @param float|null $price
     * @param string|null $currency
     * @return Classroom
     */
    public function createClassroom(string $rawName, ?float $price = null, ?string $currency = 'EUR'): Classroom
    {
        $name = $this->normalizeName($rawName);
        if ($name === '') {
            throw new \DomainException('Field "name" is required.');
        }
        if ($this->classroomRepository->findOneBy(['name' => $name])) {
            throw new \DomainException('A classrooms with that name already exists.');
        }

        $c = new Classroom();
        $c->setName($name);
        $c->setStatus(ClassroomStatusEnum::ACTIVE);

        if ($price !== null) {
            $c->setPriceCents((int)\round($price * 100));
            $c->setCurrency($currency ?? 'EUR');
        } else {
            $c->setPriceCents(0);      // treat null as free, consistent with FE "—" when 0?
            $c->setCurrency('EUR');
        }

        try {
            $this->em->persist($c);
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            throw new \DomainException('A classrooms with that name already exists.');
        }

        return $c;
    }

    /**
     * Update name and/or pricing in one place.
     * - $newName: if not null, rename (duplicate-checked by controller).
     * - $priceProvided=true & $newPrice===null → set free (0 cents).
     * - $currencyProvided=false → keep current currency.
     */
    public function updateClassroom(
        Classroom $classroom,
        ?string $newName,
        ?float $newPrice,
        ?string $newCurrency,
        bool $nameProvided = true,
        bool $priceProvided = true,
        bool $currencyProvided = false
    ): Classroom {
        if ($nameProvided && $newName !== null) {
            $classroom->setName($newName);
        }

        if ($priceProvided) {
            if ($newPrice === null) {
                $classroom->setPriceCents(0); // free
            } else {
                $classroom->setPriceCents((int)\round($newPrice * 100));
            }
            if ($currencyProvided && $newCurrency !== null) {
                $classroom->setCurrency($newCurrency);
            }
        } elseif ($currencyProvided && $newCurrency !== null) {
            $classroom->setCurrency($newCurrency);
        }

        $this->em->flush();
        return $classroom;
    }

    /**
     * Rename a classroom (duplicate-safe).
     *
     * @param Classroom $classroom
     * @param string    $rawName
     * @return Classroom
     */
    public function rename(Classroom $classroom, string $rawName): Classroom
    {
        $name = $this->normalizeName($rawName);
        if ($name === '') {
            throw new \DomainException('Field "name" is required.');
        }

        if ($name !== $classroom->getName() && $this->classroomRepository->findOneBy(['name' => $name])) {
            throw new \DomainException('A classrooms with that name already exists.');
        }

        $classroom->setName($name);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            throw new \DomainException('A classrooms with that name already exists.');
        }

        return $classroom;
    }

    /**
     * Remove a classroom.
     * - If it has ACTIVE enrollments, soft-drop it and clean relationships (returns false).
     * - If not, it is hard-deleted (returns true).
     *
     * @param Classroom $classroom
     * @return bool True when hard-deleted, false when soft-dropped.
     */
    public function removeClassroom(Classroom $classroom): bool
    {
        $activeEnrollments = $this->enrollments->countActiveByClassroom($classroom);
        if ($activeEnrollments > 0) {
            $classroom->setStatus(ClassroomStatusEnum::DROPPED);
            $classroom->setTeacher(null);
            $classroom->resetRestoreBanner();
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
     * Soft-drop the ACTIVE enrollment for $student in the given $classroom.
     *
     * @throws NotFoundHttpException when no active enrollment is found
     */
    public function removeStudentFromClassroom(User $student, Classroom $classroom): void
    {
        $this->enrollments->dropActiveForStudent($student, $classroom);
    }

    /**
     * Detach a student from all classrooms by soft-dropping all ACTIVE enrollments.
     *
     * @param User $student
     * @return void
     */
    public function detachStudentFromAnyClassroom(User $student): void
    {
        $this->enrollments->dropAllActiveForStudent($student);
        // caller controls transaction boundaries
    }

    /**
     * Reactivate a DROPPED classroom (idempotent).
     * Does not require a teacher at reactivation time.
     *
     * @param Classroom $classroom
     * @return void
     */
    public function reactivate(Classroom $classroom): void
    {
        if ($classroom->getStatus() === ClassroomStatusEnum::ACTIVE) {
            return; // idempotent
        }
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

    /**
     * Restore previously DROPPED enrollments for this classroom.
     *
     * @param Classroom $classroom
     * @return int Number of restored enrollments
     *
     * @throws ClassroomInactiveException When classroom is not ACTIVE
     */
    public function restoreRoster(Classroom $classroom): int
    {
        // Delegate to enrollment boundary; keeps classroom service slim.
        return $this->enrollments->restoreAllDroppedForClassroom($classroom);
    }

    public function dismissRestoreBanner(Classroom $classroom): void
    {
        $classroom->dismissRestoreBanner();
        $this->em->flush();
    }

    public function shouldShowRestoreBanner(Classroom $classroom, int $droppedCount): bool
    {
        return $droppedCount > 0 && !$classroom->isRestoreBannerDismissed();
    }


}
