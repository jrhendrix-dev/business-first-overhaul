<?php
declare(strict_types=1);

namespace App\Tests\Support;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Boots Symfony once per test class and manages an SQLite schema for fast tests.
 *
 * @internal Test base – not part of the production code.
 */
abstract class DatabaseTestCase extends KernelTestCase
{
    private static ?EntityManagerInterface $em = null;

    public static function setUpBeforeClass(): void
    {
        self::bootKernel();
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $tool = new SchemaTool($em);
        $tool->dropDatabase();
        $meta = $em->getMetadataFactory()->getAllMetadata();
        if ($meta !== []) {
            $tool->createSchema($meta);
        }

        self::$em = $em;
    }

    /** Shortcut to the shared EntityManager for this test class. */
    protected function em(): EntityManagerInterface
    {
        \assert(self::$em instanceof EntityManagerInterface);
        return self::$em;
    }

    protected function tearDown(): void
    {
        $this->em()->clear();
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        self::$em?->getConnection()->close();
        self::$em = null;
        parent::tearDownAfterClass();
    }
}
