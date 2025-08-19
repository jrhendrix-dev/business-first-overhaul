<?php
namespace App\Repository;

use App\Entity\Classroom;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Enum\EnrollmentStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EnrollmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Enrollment::class); }

    public function isEnrolled(User $student, Classroom $classroom): bool
    {
        return (bool) $this->createQueryBuilder('e')
            ->select('1')
            ->andWhere('e.student = :s')->setParameter('s', $student)
            ->andWhere('e.classroom = :c')->setParameter('c', $classroom)
            ->andWhere('e.status = :st')->setParameter('st', EnrollmentStatusEnum::ACTIVE)
            ->getQuery()->getOneOrNullResult();
    }

    public function findEnrollmentById(int $id): ?Enrollment
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }



    /** @return Enrollment[] */
    public function findActiveByClassroom(Classroom $classroom): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.classroom = :c')->setParameter('c', $classroom)
            ->andWhere('e.status = :st')->setParameter('st', EnrollmentStatusEnum::ACTIVE)
            ->orderBy('e.enrolledAt', 'ASC')
            ->getQuery()->getResult();
    }

    public function findOneByStudentIdAndClassId(int $studentId, int $classId): ?Enrollment
    {
        return $this->createQueryBuilder('e')
            ->andWhere('IDENTITY(e.student) = :sid')
            ->andWhere('IDENTITY(e.classroom) = :cid')
            ->setParameter('sid', $studentId)
            ->setParameter('cid', $classId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByStudentAndClassroom(User $student, Classroom $classroom): ?Enrollment
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.student = :student')
            ->andWhere('e.classroom = :classroom')
            ->setParameter('student', $student)
            ->setParameter('classroom', $classroom)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Enrollment[]
     */
    public function findByClassroomId(int $classroomId): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.student', 's')->addSelect('s')
            ->leftJoin('e.classroom', 'c')->addSelect('c')
            ->andWhere('c.id = :cid')
            ->setParameter('cid', $classroomId)
            ->getQuery()
            ->getResult();
    }

}
