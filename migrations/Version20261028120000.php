<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261028120000 extends AbstractMigration
{
    public function getDescription(): string { return 'Demo replenishment plans and selection of consumables'; }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products ADD replenishable TINYINT(1) DEFAULT 0 NOT NULL');
        // Consumable references of the educational catalogue.
        $this->addSql("UPDATE products SET replenishable = 1 WHERE reference IN ('10', '16', '22', '28', '34', '39')");
        $this->addSql('CREATE TABLE care_plans (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, product_id INT NOT NULL, quantity INT NOT NULL, interval_weeks INT NOT NULL, status VARCHAR(16) NOT NULL, next_date DATE DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_CARE_PLAN_USER (user_id), INDEX IDX_CARE_PLAN_PRODUCT (product_id), UNIQUE INDEX UNIQ_CARE_PLAN_USER_PRODUCT (user_id, product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE care_plans ADD CONSTRAINT FK_CARE_PLAN_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE care_plans ADD CONSTRAINT FK_CARE_PLAN_PRODUCT FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE care_plans');
        $this->addSql('ALTER TABLE products DROP replenishable');
    }
}
