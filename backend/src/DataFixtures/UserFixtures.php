<?php
namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Classroom;
use App\Enum\UserRoleEnum;
use App\Service\EnrollmentManager;
use App\Service\GradeManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Random\RandomException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EnrollmentManager $enrollmentManager,
        private GradeManager $gradeManager,
    ) {}

    /**
     * @throws RandomException
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('es_ES');
        $faker->unique(true); // reset unique just in case

        // --- Admin ---
        $admin = new User();
        $admin->setUserName('admin');
        $admin->setFirstName($faker->firstName());
        $admin->setLastName($faker->lastName());
        $admin->setEmail('admin@example.com');
        $admin->setRole(UserRoleEnum::ADMIN);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, '1234'));
        $manager->persist($admin);

        // --- Teachers ---
        $teachers = [];
        for ($i = 1; $i <= 3; $i++) {
            $t = new User();
            $t->setEmail($faker->unique()->safeEmail());
            $t->setFirstName($faker->firstName());
            $t->setLastName($faker->lastName());
            $t->setUserName($faker->unique()->userName());
            $t->setRole(UserRoleEnum::TEACHER);
            $t->setPassword($this->passwordHasher->hashPassword($t, '1234'));
            $manager->persist($t);
            $teachers[] = $t;
        }

        // --- Students ---
        $students = [];
        for ($i = 1; $i <= 5; $i++) {
            $s = new User();
            $s->setEmail($faker->unique()->safeEmail());
            $s->setFirstName($faker->firstName());
            $s->setLastName($faker->lastName());
            $s->setUserName($faker->unique()->userName());
            $s->setRole(UserRoleEnum::STUDENT);
            $s->setPassword($this->passwordHasher->hashPassword($s, '1234'));
            $manager->persist($s);
            $students[] = $s;
        }

        // --- Classrooms ---
        $classes = [];
        for ($i = 1; $i <= 4; $i++) {
            $c = new Classroom();
            $c->setName("Classroom {$i}");
            $manager->persist($c);
            $classes[] = $c;
        }

        // Have IDs available for EnrollmentManager/GradeManager usage
        $manager->flush();

        // --- Enrollments + Grades ---
        foreach ($classes as $idx => $classroom) {
            $slice = array_slice($students, $idx, 2);
            foreach ($slice as $student) {
                // This enforces domain rules (prevents duplicates, etc.)
                $enrollment = $this->enrollmentManager->enroll($student, $classroom);

                $gradeCount = random_int(1, 3);
                for ($g = 0; $g < $gradeCount; $g++) {
                    $component = $faker->randomElement(['Quiz', 'Homework', 'Project', 'Exam']);
                    $maxScore  = 10.0;
                    $score     = (float) random_int(5, 10);

                    $this->gradeManager->addGrade($enrollment, $component, $score, $maxScore);
                }
            }
        }

        $manager->flush();
    }
}
