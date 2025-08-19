<?php
namespace App\Repository;

use App\Entity\Enrollment;
use App\Entity\Grade;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GradeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Grade::class); }

    /** @return Grade[] */
    public function listByEnrollment(Enrollment $enrollment): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.enrollment = :e')->setParameter('e', $enrollment)
            ->orderBy('g.gradedAt', 'ASC')
            ->getQuery()->getResult();
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
