<?php

namespace App\Service;

use App\Entity\Classroom;
use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;

class ClassroomManager
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function assignTeacher(Classroom $classroom, User $user): void
    {
        if ($user->getRole() !== UserRole::TEACHER) {
            throw new \LogicException('Assigned user is not a teacher.');
        }

        $classroom->setTeacher($user);
        $this->em->persist($classroom);
        $this->em->flush();
    }

    // You can later add more: createClassroom, assignStudent, etc.
}
