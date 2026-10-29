<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Data migration: the "entretien" category mixed pool care products and accessories.
 * - floats, armchairs, sofas, vests, ladders -> new "accessoires" category
 * - matelas gonflables doubles -> "matelas"
 */
final class Version20261028090000 extends AbstractMigration
{
    private const ACCESSORY_TITLES = "(p.title LIKE 'Bouée%' OR p.title LIKE 'Fauteuil%' OR p.title LIKE 'Canapé%' OR p.title LIKE 'Gilet%' OR p.title LIKE 'Échelle%')";

    public function getDescription(): string
    {
        return 'Catégories : création de "accessoires" et répartition des produits de "entretien"';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT IGNORE INTO categories (label) VALUES ('accessoires')");

        $this->addSql("UPDATE products p
            SET p.category_id = (SELECT c.id FROM categories c WHERE c.label = 'accessoires')
            WHERE p.category_id = (SELECT c.id FROM categories c WHERE c.label = 'entretien')
              AND " . self::ACCESSORY_TITLES);

        $this->addSql("UPDATE products p
            SET p.category_id = (SELECT c.id FROM categories c WHERE c.label = 'matelas')
            WHERE p.category_id = (SELECT c.id FROM categories c WHERE c.label = 'entretien')
              AND p.title LIKE 'Matelas gonflable double%'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE products p
            SET p.category_id = (SELECT c.id FROM categories c WHERE c.label = 'entretien')
            WHERE p.category_id = (SELECT c.id FROM categories c WHERE c.label = 'matelas')
              AND p.title LIKE 'Matelas gonflable double%'");

        $this->addSql("UPDATE products p
            SET p.category_id = (SELECT c.id FROM categories c WHERE c.label = 'entretien')
            WHERE p.category_id = (SELECT c.id FROM categories c WHERE c.label = 'accessoires')");

        $this->addSql("DELETE FROM categories WHERE label = 'accessoires'");
    }
}
