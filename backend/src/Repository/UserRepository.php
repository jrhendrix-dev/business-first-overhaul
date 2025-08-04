<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Classroom;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findStudentInClassroom(int $studentId, int $classroomId): ?User
    {
        try {
            return $this->createQueryBuilder('u')
                ->andWhere('u.id = :studentId')
                ->andWhere('u.classroom = :classroom')
                ->setParameter('studentId', $studentId)
                ->setParameter(
                    'classroom',
                    $this->getEntityManager()->getReference(Classroom::class, $classroomId)
                )
                ->getQuery()
                ->getOneOrNullResult();
        } catch (ORMException $e) {
            return null;
        }
    }


}

