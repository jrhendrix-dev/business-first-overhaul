<?php
// src/DataFixtures/UserFixtures.php
declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Classroom;
use App\Enum\GradeComponentEnum;
use App\Enum\UserRoleEnum;
use App\Service\Contracts\EnrollmentPort;
use App\Service\Contracts\GradePort;  // ← alias to avoid clash with service class
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Random\RandomException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EnrollmentPort $enrollmentManager, // ← interface
        private GradePort $gradeManager,           // ← interface (aliased)
    ) {}

    /**
     * @throws RandomException
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('es_ES');
        $faker->unique(true);

        // Admin
        $admin = new User();
            $admin->setUserName('admin');
            $admin->setFirstName($faker->firstName());
            $admin->setLastName($faker->lastName());
            $admin->setEmail('admin@example.com');
            $admin->setRole(UserRoleEnum::ADMIN);
            $admin->setPassword($this->passwordHasher->hashPassword($admin, '1234'));
        $manager->persist($admin);

        // Teachers
        $teachers = [];
        for ($i = 1; $i <= 3; $i++) {
            $t = (new User())
                ->setEmail($faker->unique()->safeEmail())
                ->setFirstName($faker->firstName())
                ->setLastName($faker->lastName())
                ->setUserName($faker->unique()->userName())
                ->setRole(UserRoleEnum::TEACHER)
                ->setPassword($this->passwordHasher->hashPassword($t ?? new User(), '1234'));
            $manager->persist($t);
            $teachers[] = $t;
        }

        // Students
        $students = [];
        for ($i = 1; $i <= 5; $i++) {
            $s = (new User())
                ->setEmail($faker->unique()->safeEmail())
                ->setFirstName($faker->firstName())
                ->setLastName($faker->lastName())
                ->setUserName($faker->unique()->userName())
                ->setRole(UserRoleEnum::STUDENT)
                ->setPassword($this->passwordHasher->hashPassword($s ?? new User(), '1234'));
            $manager->persist($s);
            $students[] = $s;
        }

        // Classrooms
        $classes = [];
        for ($i = 1; $i <= 4; $i++) {
            $c = new Classroom()->setName("Classroom {$i}");
            $manager->persist($c);
            $classes[] = $c;
        }

        $manager->flush();

        // Enrollments + Grades
        foreach ($classes as $idx => $classroom) {
            $slice = array_slice($students, $idx, 2);
            foreach ($slice as $student) {
                $enrollment = $this->enrollmentManager->enroll($student, $classroom);

                $gradeCount = random_int(1, 3);
                for ($g = 0; $g < $gradeCount; $g++) {
                    $label     = $faker->randomElement(['Quiz', 'Homework', 'Project', 'Exam']);
                    $component = GradeComponentEnum::tryFromMixed($label) ?? GradeComponentEnum::QUIZ;

                    $this->gradeManager->addGrade($enrollment, $component, (float) random_int(5, 10), 10.0);
                }
            }
        }

        $manager->flush();
    }
}
