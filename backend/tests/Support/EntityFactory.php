<?php
// tests/Support/EntityFactory.php
declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\User;
use App\Entity\Classroom;
use App\Enum\UserRoleEnum;

final class EntityFactory
{
    public static function teacher(
        int $id = 1,
        string $first = 'Ana',
        string $last = 'Pérez',
        string $email = 'ana@example.com'
    ): User {
        $u = (new User())
            ->setUserName($email)
            ->setFirstName($first)
            ->setLastName($last)
            ->setEmail($email)
            ->setPassword('hash')       // irrelevant in unit test
            ->setRole(UserRoleEnum::TEACHER);

        EntityIdHelper::setId($u, $id);
        return $u;
    }

    public static function classroom(
        int $id = 2,
        string $name = 'B1',
        ?User $teacher = null
    ): Classroom {
        $c = (new Classroom())->setName($name)->setTeacher($teacher);
        EntityIdHelper::setId($c, $id);
        return $c;
    }
}
