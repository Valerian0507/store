<?php

namespace App\Tests\Entity;

use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testGetPriceEurFormatsWholeEuros(): void
    {
        $product = new Product();
        $product->setPriceCents(1000);
        $this->assertSame('10.00', $product->getPriceEur());
    }

    public function testGetPriceEurFormatsCents(): void
    {
        $product = new Product();
        $product->setPriceCents(1050);
        $this->assertSame('10.50', $product->getPriceEur());
    }

    public function testGetPriceEurHandlesZero(): void
    {
        $product = new Product();
        $product->setPriceCents(0);
        $this->assertSame('0.00', $product->getPriceEur());
    }
}


