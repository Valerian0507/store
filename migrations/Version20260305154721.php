<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305154721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE addresses (id INT AUTO_INCREMENT NOT NULL, country VARCHAR(50) NOT NULL, street VARCHAR(120) NOT NULL, postal_code VARCHAR(50) NOT NULL, user_id INT NOT NULL, INDEX IDX_6FCA7516A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE order_items (id INT AUTO_INCREMENT NOT NULL, product_name VARCHAR(255) NOT NULL, unit_price_cents INT NOT NULL, quantity INT NOT NULL, order_ref_id INT NOT NULL, product_id INT DEFAULT NULL, INDEX IDX_62809DB0E238517C (order_ref_id), INDEX IDX_62809DB04584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, status VARCHAR(50) NOT NULL, total_cents INT NOT NULL, shipping_first_name VARCHAR(120) DEFAULT NULL, shipping_last_name VARCHAR(120) DEFAULT NULL, shipping_street VARCHAR(120) DEFAULT NULL, shipping_city VARCHAR(120) DEFAULT NULL, shipping_postal_code VARCHAR(50) DEFAULT NULL, shipping_country VARCHAR(50) DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_E52FFDEEA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE addresses ADD CONSTRAINT FK_6FCA7516A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB0E238517C FOREIGN KEY (order_ref_id) REFERENCES orders (id)');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB04584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user ADD first_name VARCHAR(120) DEFAULT NULL, ADD last_name VARCHAR(120) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE addresses DROP FOREIGN KEY FK_6FCA7516A76ED395');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB0E238517C');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB04584665A');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEA76ED395');
        $this->addSql('DROP TABLE addresses');
        $this->addSql('DROP TABLE order_items');
        $this->addSql('DROP TABLE orders');
        $this->addSql('ALTER TABLE `user` DROP first_name, DROP last_name');
    }
}
