<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251104235504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD two_factor_enabled TINYINT(1) DEFAULT 0 NOT NULL, ADD totp_secret VARCHAR(255) DEFAULT NULL, ADD two_factor_recovery_codes JSON DEFAULT NULL, ADD last2_faverified_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP two_factor_enabled, DROP totp_secret, DROP two_factor_recovery_codes, DROP last2_faverified_at');
    }
}
