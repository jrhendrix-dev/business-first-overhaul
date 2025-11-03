<?php

namespace App\Repository;

use App\Entity\Classroom;
use App\Entity\User;
use App\Enum\ClassroomStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Enrollment;
use App\Enum\EnrollmentStatusEnum;

/**
 * @extends ServiceEntityRepository<Classroom>
 *
 * @method Classroom|null find($id, $lockMode = null, $lockVersion = null)
 * @method Classroom|null findOneBy(array $criteria, array $orderBy = null)
 * @method Classroom[]    findAll()
 * @method Classroom[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClassroomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Classroom::class);
    }

    /**
     * Returns all classrooms without a teacher assigned.
     *
     * @return Classroom[]
     */
    public function findUnassigned(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.teacher IS NULL')
            ->andWhere('c.status = :status')
            ->setParameter('status', ClassroomStatusEnum::ACTIVE)
            ->getQuery()
            ->getResult();
    }


    /**
     * Returns all classrooms assigned to a specific teacher.
     *
     * @param int $teacherId The ID of the teacher.
     *
     * @return Classroom[]
     */
    public function findByTeacher(int $teacherId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.teacher = :teacherId')
            ->andWhere('c.status = :status')
            ->setParameter('teacherId', $teacherId)
            ->setParameter('status', ClassroomStatusEnum::ACTIVE)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param User $teacher
     * @return array
     */
    public function findActiveByTeacher(User $teacher): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.id AS id, c.name AS name')
            ->andWhere('c.teacher = :t')
            ->andWhere('c.status = :s')
            ->setParameter('t', $teacher)
            ->setParameter('s', 'ACTIVE')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Returns the classrooms in which a specific student is enrolled.
     *
     * @param int $studentId The ID of the student.
     *
     * @return Classroom[]
     */
    public function findByStudent(int $studentId): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin(Enrollment::class, 'e', 'WITH', 'e.classroom = c')
            ->andWhere('e.student = :sid')
            ->andWhere('c.status = :status')
            ->setParameter('sid', $studentId)
            ->setParameter('status', ClassroomStatusEnum::ACTIVE)
            ->getQuery()
            ->getResult();
    }

    /**
     * Searches classrooms by name.
     *
     * @param string $term The name search term.
     * @return array<Classroom>
     */
    public function searchByName(string $term): array {
        return $this->createQueryBuilder('c')
            ->andWhere('c.name LIKE :term')
            ->andWhere('c.status = :status')
            ->setParameter('term', '%' . $term . '%')
            ->setParameter('status', ClassroomStatusEnum::ACTIVE)
            ->getQuery()
            ->getResult();
    }

    /**
     * Counts the number of classrooms assigned to a specific teacher.
     *
     * @param int $teacherId
     * @return int
     */
    public function countByTeacher(int $teacherId): int {
        return (int) $this->createQueryBuilder('c')
            ->select('count(c.id)')
            ->andWhere('c.teacher = :teacherId')
            ->andWhere('c.status = :status')
            ->setParameter('teacherId', $teacherId)
            ->setParameter('status', ClassroomStatusEnum::ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Classroom[]
     */
    public function findAllByTeacher(User $teacher): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.teacher = :teacher')
            ->andWhere('c.status = :status')
            ->setParameter('teacher', $teacher)
            ->setParameter('status', ClassroomStatusEnum::ACTIVE)
            ->getQuery()
            ->getResult();
    }

    public function findAllWithTeacher(bool $includeDropped = false): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.teacher', 't')->addSelect('t')
            ->orderBy('c.name', 'ASC');

        if (!$includeDropped) {
            $qb->andWhere('c.status = :status')->setParameter('status', ClassroomStatusEnum::ACTIVE);
        }

        return $qb->getQuery()->getResult();
    }


    /** @return Classroom[] */
    public function findByNameWithTeacher(string $name, bool $includeDropped = false): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.teacher', 't')->addSelect('t')
            ->andWhere('c.name LIKE :n')->setParameter('n', '%'.$name.'%')
            ->orderBy('c.name', 'ASC');

        if (!$includeDropped) {
            $qb->andWhere('c.status = :status')->setParameter('status', ClassroomStatusEnum::ACTIVE);
        }

        return $qb->getQuery()->getResult();
    }

    public function countActiveByClassroom(Classroom $classroom): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(e.id)')
            ->leftJoin('c.enrollments', 'e')
            ->andWhere('c = :classroom')->setParameter('classroom', $classroom)
            ->andWhere('e.status = :status')->setParameter('status', EnrollmentStatusEnum::ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
