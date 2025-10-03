<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250928202542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize legacy numeric roles to string-backed enum values';
    }

    public function up(Schema $schema): void
    {
        // If any rows still have old ints-as-strings, map them:
        $this->addSql("UPDATE users SET role = 'ROLE_ADMIN'   WHERE role IN ('1','ADMIN')");
        $this->addSql("UPDATE users SET role = 'ROLE_TEACHER' WHERE role IN ('2','TEACHER')");
        $this->addSql("UPDATE users SET role = 'ROLE_STUDENT' WHERE role IN ('3','STUDENT')");
    }

    public function down(Schema $schema): void
    {
        // Rollback to legacy ints if ever needed
        $this->addSql("UPDATE users SET role = '1' WHERE role = 'ROLE_ADMIN'");
        $this->addSql("UPDATE users SET role = '2' WHERE role = 'ROLE_TEACHER'");
        $this->addSql("UPDATE users SET role = '3' WHERE role = 'ROLE_STUDENT'");
    }
}
