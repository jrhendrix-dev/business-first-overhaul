<?php
// src/Service/Contracts/EnrollmentPort.php
namespace App\Service\Contracts;

use App\Entity\Classroom;
use App\Entity\Enrollment;
use App\Entity\User;

interface EnrollmentPort
{
    public function enroll(User $student, Classroom $classroom): Enrollment;

    public function dropActiveForStudent(User $student, ?Classroom $classroom = null): void;

    public function dropAllActiveForStudent(User $student): void;


    public function dropAllActiveForClassroom(Classroom $classroom): void;

}
