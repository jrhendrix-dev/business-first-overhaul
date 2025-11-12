<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Safely adds `meta` to classes if the table already exists (CI-friendly).
 */
final class Version20251029181519 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add meta JSON to classes if table exists (skip on fresh DBs).';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        if ($sm->tablesExist(['classes'])) {
            $cols = $sm->listTableColumns('classes');
            if (!isset($cols['meta'])) {
                $this->addSql('ALTER TABLE classes ADD meta JSON NOT NULL');
            }
        }
        // If the table doesn’t exist, do nothing — a later migration will create it with `meta`.
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        if ($sm->tablesExist(['classes'])) {
            $cols = $sm->listTableColumns('classes');
            if (isset($cols['meta'])) {
                $this->addSql('ALTER TABLE classes DROP meta');
            }
        }
    }
}
