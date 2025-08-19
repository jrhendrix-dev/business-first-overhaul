<?php
// tests/Integration/DatabaseCleanupTrait.php

declare(strict_types=1);

namespace App\Tests\Integration;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Ensures a clean database before tests by truncating all tables.
 */
trait DatabaseCleanupTrait
{
    protected function truncateDatabase(EntityManagerInterface $em): void
    {
        $purger = new ORMPurger($em);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
        $purger->purge();
    }
}
