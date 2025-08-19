<?php
declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Classroom;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Test-only factory for creating Classroom entities with unique names.
 */
final class ClassroomFactory
{
    private static int $counter = 1;

    public static function make(?string $name = null): Classroom
    {
        $n = self::$counter++;
        $c = new Classroom();
        $c->setName($name ?? sprintf('Class-%03d', $n));
        return $c;
    }

    public static function create(EntityManagerInterface $em, ?string $name = null): Classroom
    {
        $c = self::make($name);
        $em->persist($c);
        $em->flush();
        return $c;
    }
}
