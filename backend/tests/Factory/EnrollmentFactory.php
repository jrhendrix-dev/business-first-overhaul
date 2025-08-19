<?php
declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Classroom;
use App\Entity\Enrollment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Test-only helper to persist an Enrollment (assumes User/Classroom already persisted).
 */
final class EnrollmentFactory
{
    public static function create(EntityManagerInterface $em, User $student, Classroom $classroom): Enrollment
    {
        $e = new Enrollment();
        $e->setStudent($student);
        $e->setClassroom($classroom);
        $em->persist($e);
        $em->flush();
        return $e;
    }
}
