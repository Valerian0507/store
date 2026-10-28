<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261028084108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Newsletter : table des abonnés';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE newsletter_subscribers (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, unsubscribe_token VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_19234963E7927C74 (email), UNIQUE INDEX UNIQ_19234963E0674361 (unsubscribe_token), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE newsletter_subscribers');
    }
}
