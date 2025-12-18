<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251113223533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_DBDCD7E16278D5A8');
        $this->addSql('ALTER TABLE classrooms DROP FOREIGN KEY FK_2ED7EC541807E1D');
        $this->addSql('ALTER TABLE password_reset_tokens DROP FOREIGN KEY FK_3967A216A76ED395');
        $this->addSql('DROP TABLE classrooms');
        $this->addSql('DROP TABLE password_reset_tokens');
        $this->addSql('ALTER TABLE classes ADD teacher_id INT DEFAULT NULL, ADD status VARCHAR(255) NOT NULL, DROP description, DROP schedule, DROP capacity, DROP created_at, DROP updated_at, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE name name VARCHAR(45) NOT NULL, CHANGE currency currency VARCHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE classes ADD CONSTRAINT FK_2ED7EC541807E1D FOREIGN KEY (teacher_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_2ED7EC541807E1D ON classes (teacher_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_class_name ON classes (name)');
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_DBDCD7E16278D5A8');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_DBDCD7E16278D5A8 FOREIGN KEY (classroom_id) REFERENCES classes (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE classrooms (id INT AUTO_INCREMENT NOT NULL, teacher_id INT DEFAULT NULL, name VARCHAR(45) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, status VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'ACTIVE\' NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_2ED7EC541807E1D (teacher_id), UNIQUE INDEX uniq_class_name (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE password_reset_tokens (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token_hash VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', used_at DATETIME DEFAULT NULL, request_ip VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_3967A216F9D83E2 (expires_at), INDEX IDX_3967A216A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE classrooms ADD CONSTRAINT FK_2ED7EC541807E1D FOREIGN KEY (teacher_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE password_reset_tokens ADD CONSTRAINT FK_3967A216A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE enrollment DROP FOREIGN KEY FK_DBDCD7E16278D5A8');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_DBDCD7E16278D5A8 FOREIGN KEY (classroom_id) REFERENCES classrooms (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classes DROP FOREIGN KEY FK_2ED7EC541807E1D');
        $this->addSql('DROP INDEX IDX_2ED7EC541807E1D ON classes');
        $this->addSql('DROP INDEX uniq_class_name ON classes');
        $this->addSql('ALTER TABLE classes ADD description LONGTEXT DEFAULT NULL, ADD schedule VARCHAR(255) DEFAULT NULL, ADD capacity INT UNSIGNED DEFAULT NULL, ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME DEFAULT NULL, DROP teacher_id, DROP status, CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE currency currency CHAR(3) NOT NULL');
    }
}
