<?php

namespace App\Tests\Entity;

use App\Entity\OrderItem;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de la logique métier de la ligne de commande.
 * Aucune base de données n'est nécessaire : on teste uniquement le calcul
 * du total de ligne, la protection de la quantité et la copie « snapshot »
 * des données produit au moment de la commande.
 */
class OrderItemTest extends TestCase
{
    public function testGetLineTotalCentsMultipliesUnitPriceByQuantity(): void
    {
        $item = new OrderItem();
        $item->setUnitPriceCents(1500);
        $item->setQuantity(3);

        // 1500 * 3 = 4500
        $this->assertSame(4500, $item->getLineTotalCents());
    }

    public function testSetQuantityNeverGoesBelowOne(): void
    {
        $item = new OrderItem();

        // La quantité est protégée par max(1, $qty) : 0 et les valeurs
        // négatives sont ramenées à 1.
        $item->setQuantity(0);
        $this->assertSame(1, $item->getQuantity());

        $item->setQuantity(-5);
        $this->assertSame(1, $item->getQuantity());

        $item->setQuantity(4);
        $this->assertSame(4, $item->getQuantity());
    }

    public function testFromProductCopiesProductSnapshot(): void
    {
        $product = new Product();
        $product->setTitle('Pompe de filtration');
        $product->setPriceCents(2599);
        $product->setImage('pompe.jpg');

        $item = OrderItem::fromProduct($product, 2);

        // Les données sont recopiées dans la ligne (snapshot) : même si le
        // produit est modifié ou supprimé ensuite, la commande reste figée.
        $this->assertSame('Pompe de filtration', $item->getProductName());
        $this->assertSame(2599, $item->getUnitPriceCents());
        $this->assertSame('pompe.jpg', $item->getProductImage());
        $this->assertSame(2, $item->getQuantity());
        $this->assertSame(5198, $item->getLineTotalCents());
        $this->assertSame($product, $item->getProduct());
    }

    public function testFromProductUsesZeroPriceWhenProductPriceIsNull(): void
    {
        $product = new Product();
        $product->setTitle('Produit sans prix');
        // Aucun prix défini : getPriceCents() vaut null, le snapshot retombe sur 0.

        $item = OrderItem::fromProduct($product, 1);

        $this->assertSame(0, $item->getUnitPriceCents());
        $this->assertSame(0, $item->getLineTotalCents());
    }
}
