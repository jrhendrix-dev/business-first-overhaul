<?php
// src/Repository/GradeRepository.php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Enrollment;
use App\Entity\Grade;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;

/**
 * @extends ServiceEntityRepository<Grade>
 */
 class GradeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Grade::class);
    }

    /**
     * Fetch a grade with its enrollment, classroom and student associations eagerly loaded.
     */
    public function findOneWithRelations(int $id): ?Grade
    {
        return $this->createQueryBuilder('g')
            ->addSelect('e', 'c', 's')
            ->leftJoin('g.enrollment', 'e')
            ->leftJoin('e.classroom', 'c')
            ->leftJoin('e.student', 's')
            ->andWhere('g.id = :id')->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Grade[]
     */
    public function listByEnrollment(Enrollment $enrollment): array
    {
        return $this->createQueryBuilder('g')
            ->addSelect('e', 'c', 's')
            ->leftJoin('g.enrollment', 'e')
            ->leftJoin('e.classroom', 'c')
            ->leftJoin('e.student', 's')
            ->andWhere('g.enrollment = :enrollment')->setParameter('enrollment', $enrollment)
            ->orderBy('g.gradedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Grade[]
     */
    public function listForStudent(User $student, ?int $classId = null): array
    {
        $qb = $this->createQueryBuilder('g')
            ->addSelect('e', 'c')
            ->leftJoin('g.enrollment', 'e')
            ->leftJoin('e.classroom', 'c')
            ->andWhere('e.student = :student')->setParameter('student', $student)
            ->orderBy('g.gradedAt', 'ASC');

        if ($classId !== null) {
            $qb->andWhere('c.id = :classId')->setParameter('classId', $classId);
        }

        return $qb->getQuery()->getResult();
    }

     /**
      * List every grade in a classroom, eager-loading enrollment, classroom and student.
      *
      * @return Grade[]
      */
     public function listForClass(int $classId): array
     {
         return $this->createQueryBuilder('g')
             ->addSelect('e', 'c', 's')
             ->leftJoin('g.enrollment', 'e')
             ->leftJoin('e.classroom', 'c')
             ->leftJoin('e.student', 's')
             ->andWhere('c.id = :cid')->setParameter('cid', $classId)
             ->orderBy('s.lastName', 'ASC')
             ->addOrderBy('s.firstName', 'ASC')
             ->addOrderBy('g.gradedAt', 'ASC')
             ->getQuery()
             ->getResult();
     }

     /**
      * @return Grade[]
      */
     public function listForTeacher(User $teacher): array
     {
         return $this->createQueryBuilder('g')
             ->addSelect('e', 'c', 's')
             ->leftJoin('g.enrollment', 'e')
             ->leftJoin('e.classroom', 'c')
             ->leftJoin('e.student',  's')
             ->andWhere('c.teacher = :t')->setParameter('t', $teacher)
             ->orderBy('c.name', 'ASC')
             ->addOrderBy('s.lastName', 'ASC')
             ->addOrderBy('s.firstName', 'ASC')
             ->addOrderBy('g.gradedAt', 'ASC')
             ->getQuery()
             ->getResult();
     }

     /**
      * @return Grade[]
      */
     public function listAllWithRelations(): array
     {
         return $this->createQueryBuilder('g')
             ->addSelect('e', 'c', 's')
             ->leftJoin('g.enrollment', 'e')
             ->leftJoin('e.classroom', 'c')
             ->leftJoin('e.student',  's')
             ->orderBy('c.name', 'ASC')
             ->addOrderBy('s.lastName', 'ASC')
             ->addOrderBy('s.firstName', 'ASC')
             ->addOrderBy('g.gradedAt', 'ASC')
             ->getQuery()
             ->getResult();
     }

    public function averagePercentFor(Enrollment $enrollment): float
    {
        $rows = $this->createQueryBuilder('g')
            ->select('SUM(g.score) as s, SUM(g.maxScore) as m')
            ->andWhere('g.enrollment = :e')->setParameter('e', $enrollment)
            ->getQuery()->getSingleResult();

        $s = (float) ($rows['s'] ?? 0);
        $m = (float) ($rows['m'] ?? 0);
        return $m > 0 ? ($s / $m) * 100.0 : 0.0;
    }
}
