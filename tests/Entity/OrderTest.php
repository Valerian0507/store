<?php

namespace App\Tests\Entity;

use App\Entity\Order;
use App\Entity\OrderItem;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de la commande : état initial, ajout de lignes,
 * lien inverse ligne -> commande, et recalcul du montant total.
 */
class OrderTest extends TestCase
{
    public function testNewOrderHasPendingStatusAndZeroTotal(): void
    {
        $order = new Order();

        $this->assertSame('pending', $order->getStatus());
        $this->assertSame(0, $order->getTotalCents());
        $this->assertCount(0, $order->getItems());
    }

    public function testAddItemStoresItemAndSetsBackReference(): void
    {
        $order = new Order();
        $item = (new OrderItem())->setUnitPriceCents(1000)->setQuantity(1);

        $order->addItem($item);

        $this->assertCount(1, $order->getItems());
        // Le lien inverse est positionné : la ligne connaît sa commande.
        $this->assertSame($order, $item->getOrder());
    }

    public function testAddItemIgnoresDuplicate(): void
    {
        $order = new Order();
        $item = (new OrderItem())->setUnitPriceCents(1000)->setQuantity(1);

        $order->addItem($item);
        $order->addItem($item);

        // La même ligne n'est pas ajoutée deux fois.
        $this->assertCount(1, $order->getItems());
    }

    public function testRecalcTotalSumsLineTotals(): void
    {
        $order = new Order();
        $order->addItem((new OrderItem())->setUnitPriceCents(1000)->setQuantity(2)); // 2000
        $order->addItem((new OrderItem())->setUnitPriceCents(500)->setQuantity(3));  // 1500

        $order->recalcTotal();

        // 2000 + 1500 = 3500
        $this->assertSame(3500, $order->getTotalCents());
    }

    public function testRemoveItemRecalculatesTotal(): void
    {
        $order = new Order();
        $first = (new OrderItem())->setUnitPriceCents(1000)->setQuantity(2);  // 2000
        $second = (new OrderItem())->setUnitPriceCents(500)->setQuantity(3);  // 1500

        $order->addItem($first);
        $order->addItem($second);
        $order->recalcTotal();
        $this->assertSame(3500, $order->getTotalCents());

        // removeItem() recalcule automatiquement le total.
        $order->removeItem($second);

        $this->assertCount(1, $order->getItems());
        $this->assertSame(2000, $order->getTotalCents());
    }
}
