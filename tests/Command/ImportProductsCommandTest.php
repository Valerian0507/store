<?php

namespace App\Tests\Command;

use App\Entity\Product;
use App\Tests\Support\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * app:import-products: creation, then update by reference.
 * A new import updates the product but keeps the stock managed in the admin.
 */
class ImportProductsCommandTest extends KernelTestCase
{
    use DatabaseTestTrait;

    private string $file;

    protected function setUp(): void
    {
        static::bootKernel();
        $this->resetDatabase();
        $this->file = (string) tempnam(sys_get_temp_dir(), 'catalogue');
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        parent::tearDown();
    }

    public function testImportCreatesThenUpdatesWithoutResettingStock(): void
    {
        $this->writeCatalogue('Piscine de test', 199.9);
        $tester = $this->runImport();

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('Imported products: 1, updated: 0', $tester->getDisplay());

        $product = $this->product();
        self::assertSame('Piscine de test', $product->getTitle());
        self::assertSame(19990, $product->getPriceCents());
        self::assertSame(50, $product->getStock());
        self::assertSame('piscines', $product->getCategory()?->getLabel());
        self::assertSame('Description de test.', $product->getDescription());

        // Stock changed in the admin (replenishment, sales…)
        $this->em()->getConnection()->executeStatement("UPDATE products SET stock = 7 WHERE reference = 'IMP-1'");

        $this->writeCatalogue('Piscine de test renommée', 149.0);
        $tester = $this->runImport();

        self::assertStringContainsString('Imported products: 0, updated: 1', $tester->getDisplay());
        $this->em()->clear();
        $product = $this->product();
        self::assertSame('Piscine de test renommée', $product->getTitle());
        self::assertSame(14900, $product->getPriceCents());
        self::assertSame(7, $product->getStock());
    }

    public function testMissingFileFails(): void
    {
        $tester = new CommandTester((new Application(self::$kernel))->find('app:import-products'));
        $tester->execute(['file' => 'introuvable.json']);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('File not found', $tester->getDisplay());
    }

    private function writeCatalogue(string $title, float $price): void
    {
        file_put_contents($this->file, json_encode(['catalogue' => [[
            'reference' => 'IMP-1',
            'category' => 'piscines',
            'title' => $title,
            'description' => 'Description de test.',
            'price_eur' => $price,
        ]]], JSON_THROW_ON_ERROR));
    }

    private function runImport(): CommandTester
    {
        $tester = new CommandTester((new Application(self::$kernel))->find('app:import-products'));
        $tester->execute(['file' => $this->file]);

        return $tester;
    }

    private function product(): Product
    {
        $product = $this->em()->getRepository(Product::class)->findOneBy(['reference' => 'IMP-1']);
        self::assertInstanceOf(Product::class, $product);

        return $product;
    }
}
