<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328090702 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX reference ON orders');
        $this->addSql('DROP INDEX created_at ON orders');
        $this->addSql('ALTER TABLE orders CHANGE user_id user_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orders CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX reference ON orders (reference)');
        $this->addSql('CREATE UNIQUE INDEX created_at ON orders (created_at)');
    }
}
