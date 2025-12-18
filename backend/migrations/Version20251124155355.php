<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251124155355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE blog_posts (id INT AUTO_INCREMENT NOT NULL, author_id INT NOT NULL, title VARCHAR(180) NOT NULL, slug VARCHAR(200) NOT NULL, excerpt LONGTEXT DEFAULT NULL, content_html LONGTEXT NOT NULL, cover_image_url VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_78B2F932989D9B62 (slug), INDEX IDX_78B2F932F675F31B (author_id), INDEX blog_posts_slug_idx (slug), INDEX blog_posts_status_published_idx (status, published_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE blog_posts ADD CONSTRAINT FK_78B2F932F675F31B FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE blog_posts DROP FOREIGN KEY FK_78B2F932F675F31B');
        $this->addSql('DROP TABLE blog_posts');
    }
}
