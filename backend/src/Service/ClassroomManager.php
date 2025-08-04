<?php

namespace App\Service;

use App\Entity\Classroom;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ClassroomRepository;

// ADD FUNCTIONS HERE THAT MODIFY THE DB
/**
 * Service responsible for managing business logic related to Classroom entities.
 */
class ClassroomManager
{
    /**
     * @param EntityManagerInterface $em Doctrine's entity manager for persistence.
     * @param ClassroomRepository $classroomRepository Repository for custom classroom queries.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private ClassroomRepository $classroomRepository,
    ) {}

    /**
     * Assigns a teacher to a classroom after validating the role.
     *
     * @param Classroom $classroom The classroom to assign the teacher to.
     * @param User $teacher The user to assign as teacher.
     *
     * @throws \LogicException If the user is not a teacher.
     */
    public function assignTeacher(Classroom $classroom, User $teacher): void
    {
        if(!$teacher->isTeacher()){
            throw new \LogicException('Only teachers can be assigned to a classroom as a teacher');
        }

        $classroom->setTeacher($teacher);

        $this->em->persist($classroom); // technically optional if already managed

        $this->em->flush(); // Assuming classroom is already managed
    }

    /**
     * Assigns a student to a classroom after validating the role.
     *
     * @param Classroom $classroom The classroom to assign the student to.
     * @param User $student The user to assign as student.
     *
     * @throws \LogicException If the user is not a student.
     */
    public function assignStudent(Classroom $classroom, User $student): void
    {
        if(!$student->isStudent()){
            throw new \LogicException('Only students can be assigned to a classroom');
        }

        $classroom->addStudent($student);
        $this->em->flush();
    }

    /**
     * Unassigns all teachers and students from a classroom.
     *
     * @param Classroom $classroom The classroom to reset.
     */
    public function unassignAll(Classroom $classroom): void
    {
        // Unassign teacher
        $classroom->setTeacher(null);

        // Unassign students properly (owning side must be updated)
        foreach ($classroom->getStudents()->toArray() as $student) {   //->toArray() avoids modifying the collection during iteration, which is safer.
            $classroom->removeStudent($student);
        }

        $this->em->flush(); // Persist all changes
    }

    /**
     * Retrieves all classrooms without an assigned teacher.
     *
     * @return Classroom[] List of unassigned classrooms.
     */
    public function getUnassignedClassrooms(): array
    {
        return $this->classroomRepository->findUnassigned();
    }

    public function removeStudentFromClassroom(User $student): void
    {
        $classroom = $student->getClassroom();

        if ($classroom !== null) {
            $classroom->removeStudent($student);
        }

        $this->em->flush();
    }
}
