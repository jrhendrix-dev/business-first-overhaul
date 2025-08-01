<?php

// src/DataFixtures/UserFixtures.php
namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Classroom;
use App\Enum\UserRoleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('es_ES'); //'es_ES' makes it use Spanish-like names

        // Create 3 teachers
        for ($i = 1; $i <= 3; $i++) {
            $teacher = new User();
            $teacher->setEmail("teacher$i@example.com");
            $teacher->setFirstname($faker->firstName);
            $teacher->setLastname($faker->lastName);
            $teacher->setUsername($faker->userName);
            $hashedPassword = $this->passwordHasher->hashPassword($teacher, '1234');
            $teacher->setPassword($hashedPassword);
            $teacher->setRole(UserRoleEnum::TEACHER);
            $manager->persist($teacher);
        }

        // Create 5 students
        $students = [];
        for ($i = 1; $i <= 5; $i++) {
            $student = new User();
            $student->setEmail("student$i@example.com");
            $student->setFirstname($faker->firstName());
            $student->setLastname($faker->lastName());
            $student->setUsername($faker->userName);
            $hashedPassword = $this->passwordHasher->hashPassword($student, '1234');
            $student->setPassword($hashedPassword);
            $student->setRole(UserRoleEnum::STUDENT);
            $manager->persist($student);
            $students[] = $student;
        }

        // Create 2 classrooms
        for ($i = 1; $i <= 2; $i++) {
            $classroom = new Classroom();
            $classroom->setName("Classroom $i");
            // Assign 2 students
            foreach (array_slice($students, ($i - 1) * 2, 2) as $s) {
                $classroom->addStudent($s);
            }
            $manager->persist($classroom);
        }

        $manager->flush();
    }
}
