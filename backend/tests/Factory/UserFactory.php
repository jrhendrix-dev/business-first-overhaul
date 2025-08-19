<?php
// tests/Factory/UserFactory.php
declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\User;
use App\Enum\UserRoleEnum;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Test-only factory for creating User entities with guaranteed-unique fields.
 */
final class UserFactory
{
    private static int $counter = 1;

    /**
     * Create a non-persisted User with sane defaults.
     *
     * @param UserRoleEnum $role
     * @param array{
     *   username?:string,
     *   email?:string,
     *   firstName?:string,
     *   lastName?:string,
     *   password?:string
     * } $overrides
     */
    public static function make(UserRoleEnum $role, array $overrides = []): User
    {
        $n = self::$counter++;

        $u = new User();
        $u->setRole($role);

        // REQUIRED fields – always set defaults
        $u->setFirstName($overrides['firstName'] ?? sprintf('First%d', $n));
        $u->setLastName($overrides['lastName'] ?? sprintf('Last%d', $n));

        // Keep these unique & short enough for your columns
        $u->setUsername($overrides['username'] ?? sprintf('u%06d', $n));
        $u->setEmail($overrides['email'] ?? sprintf('u%06d@example.test', $n));

        // If your column is NOT NULL, provide a dummy password (hashing not needed for tests)
        $u->setPassword($overrides['password'] ?? 'x');

        return $u;
    }

    /**
     * Create and persist a User in one call.
     */
    public static function create(EntityManagerInterface $em, UserRoleEnum $role, array $overrides = []): User
    {
        $u = self::make($role, $overrides);
        $em->persist($u);
        $em->flush();

        return $u;
    }
}
