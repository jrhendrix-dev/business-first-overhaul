<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Column;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ensure classes exists and has: price_cents (UINT), currency (CHAR(3)), and meta JSON.
 */
final class Version20251108120731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure classes table exists with price fields and meta column.';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        if (!$sm->tablesExist(['classes'])) {
            $this->addSql(<<<'SQL'
CREATE TABLE classes (
  id INT UNSIGNED AUTO_INCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL,
  description LONGTEXT DEFAULT NULL,
  schedule VARCHAR(255) DEFAULT NULL,
  capacity INT UNSIGNED DEFAULT NULL,
  price_cents INT UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL,
  meta JSON NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
            return;
        }

        // Table exists → add missing columns only
        /** @var array<string, Column> $cols */
        $cols = $sm->listTableColumns('classes');

        if (!isset($cols['price_cents'])) {
            $this->addSql('ALTER TABLE classes ADD price_cents INT UNSIGNED NOT NULL');
        }
        if (!isset($cols['currency'])) {
            $this->addSql('ALTER TABLE classes ADD currency CHAR(3) NOT NULL');
        }
        if (!isset($cols['meta'])) {
            $this->addSql('ALTER TABLE classes ADD meta JSON NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        if ($sm->tablesExist(['classes'])) {
            $cols = $sm->listTableColumns('classes');

            if (isset($cols['meta'])) {
                $this->addSql('ALTER TABLE classes DROP meta');
            }
            if (isset($cols['currency'])) {
                $this->addSql('ALTER TABLE classes DROP currency');
            }
            if (isset($cols['price_cents'])) {
                $this->addSql('ALTER TABLE classes DROP price_cents');
            }
        }
    }
}
