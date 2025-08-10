<?php

namespace App\Repository;

use App\Entity\Classroom;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
            ->setParameter('teacherId', $teacherId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns the classroom in which a specific student is enrolled.
     *
     * @param int $studentId The ID of the student.
     *
     * @return Classroom[]
     */
    public function findByStudent(int $studentId): array
    {

        return $this->createQueryBuilder('c')
            ->join('c.students', 's')
            ->andWhere('s.id = :studentId')
            ->setParameter('studentId', $studentId)
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
            ->setParameter('term', '%' . $term . '%')
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
            ->setParameter('teacherId', $teacherId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
