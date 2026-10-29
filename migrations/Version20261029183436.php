<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261029183436 extends AbstractMigration
{
    public function getDescription(): string { return 'Cart: one row per product (unique index never created by the previous mapping)'; }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX uniq_cart_product ON cart_items (cart_id, product_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_cart_product ON cart_items');
    }
}
