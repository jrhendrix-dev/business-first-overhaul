<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251008120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure existing classrooms have ACTIVE status and enforce default value.';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();

        if ($platform === 'mysql') {
            $this->addSql("UPDATE classrooms SET status = 'ACTIVE' WHERE status IS NULL OR status = ''");
            $this->addSql("ALTER TABLE classrooms MODIFY status VARCHAR(255) NOT NULL DEFAULT 'ACTIVE'");
            return;
        }

        if ($platform === 'postgresql') {
            $this->addSql("UPDATE classrooms SET status = 'ACTIVE' WHERE status IS NULL OR status = ''");
            $this->addSql("ALTER TABLE classrooms ALTER COLUMN status SET DEFAULT 'ACTIVE'");
            $this->addSql("ALTER TABLE classrooms ALTER COLUMN status SET NOT NULL");
            return;
        }

        $this->abortIf(true, sprintf('Unsupported platform: %s', $platform));
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();

        if ($platform === 'mysql') {
            $this->addSql("ALTER TABLE classrooms MODIFY status VARCHAR(255) NOT NULL");
            return;
        }

        if ($platform === 'postgresql') {
            $this->addSql("ALTER TABLE classrooms ALTER COLUMN status DROP DEFAULT");
            return;
        }

        $this->abortIf(true, sprintf('Unsupported platform: %s', $platform));
    }
}
