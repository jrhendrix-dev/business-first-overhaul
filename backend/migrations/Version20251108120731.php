<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds price fields to classes; creates the table if it does not exist (CI-friendly).
 *
 * @phpstan-type ColumnMap array<string, Column>
 */
final class Version20251108120731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure classes table exists and has price_cents (UINT) and currency (CHAR(3)).';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        // 1) Create table if missing (so CI fresh DBs pass)
        if (!$sm->tablesExist(['classes'])) {
            $this->addSql(<<<'SQL'
CREATE TABLE classes (
  id INT UNSIGNED AUTO_INCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL,
  description LONGTEXT DEFAULT NULL,
  schedule VARCHAR(255) DEFAULT NULL,
  capacity INT UNSIGNED DEFAULT NULL,
  -- New pricing fields
  price_cents INT UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
            return;
        }

        // 2) Table exists → add the missing columns only
        /** @var Column[] $cols */
        $cols = $sm->listTableColumns('classes');

        if (!isset($cols['price_cents'])) {
            $this->addSql('ALTER TABLE classes ADD price_cents INT UNSIGNED NOT NULL');
        }
        if (!isset($cols['currency'])) {
            // use CHAR(3) to enforce ISO code length
            $this->addSql('ALTER TABLE classes ADD currency CHAR(3) NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        if ($sm->tablesExist(['classes'])) {
            /** @var Column[] $cols */
            $cols = $sm->listTableColumns('classes');

            if (isset($cols['currency'])) {
                $this->addSql('ALTER TABLE classes DROP currency');
            }
            if (isset($cols['price_cents'])) {
                $this->addSql('ALTER TABLE classes DROP price_cents');
            }
        }
    }
}
