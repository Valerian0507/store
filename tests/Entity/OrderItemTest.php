<?php

namespace App\Tests\Entity;

use App\Entity\OrderItem;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests of the order line business logic.
 * No database needed: only the line total calculation,
 * the quantity guard and the "snapshot" copy of the product data
 * at order time are tested.
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

        // The quantity is guarded by max(1, $qty): 0 and negative
        // values are brought back to 1.
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

        // The data is copied into the line (snapshot): even if the
        // product is changed or deleted later, the order stays unchanged.
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
        // No price set: getPriceCents() is null, the snapshot falls back to 0.

        $item = OrderItem::fromProduct($product, 1);

        $this->assertSame(0, $item->getUnitPriceCents());
        $this->assertSame(0, $item->getLineTotalCents());
    }
}
