<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Tests\Support\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Deleting a product removes its photo from public/uploads/products,
 * unless a past order still shows it (order history, confirmation email).
 */
class ProductImageDeletionTest extends WebTestCase
{
    use DatabaseTestTrait;

    private KernelBrowser $client;
    private string $imagePath;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->resetDatabase();

        $image = 'phpunit-' . bin2hex(random_bytes(4)) . '.jpg';
        $this->imagePath = static::getContainer()->getParameter('product_images_directory') . '/' . $image;
        file_put_contents($this->imagePath, 'fake image');
    }

    protected function tearDown(): void
    {
        @unlink($this->imagePath);
        parent::tearDown();
    }

    public function testDeletingProductRemovesItsPhoto(): void
    {
        $product = $this->createProductWithImage();

        $this->deleteFromAdmin($product);

        self::assertResponseRedirects('/admin/products');
        self::assertFileDoesNotExist($this->imagePath);
    }

    public function testPhotoUsedByAnOrderIsKept(): void
    {
        $product = $this->createProductWithImage();
        $this->createOrderShowing($product);

        $this->deleteFromAdmin($product);

        self::assertResponseRedirects('/admin/products');
        self::assertFileExists($this->imagePath);
    }

    private function createProductWithImage(): Product
    {
        $product = $this->createProduct('IMG-1', 'Produit avec photo');
        $product->setImage(basename($this->imagePath));
        $this->em()->flush();

        return $product;
    }

    private function createOrderShowing(Product $product): void
    {
        $item = (new OrderItem())
            ->setProduct($product)
            ->setProductName((string) $product->getTitle())
            ->setProductImage($product->getImage())
            ->setUnitPriceCents(1990)
            ->setQuantity(1);

        $order = (new Order())
            ->setUser($this->createUser('acheteur@example.test'))
            ->setReference('ORD-IMG-0001')
            ->setTotalCents(1990);
        $order->addItem($item);

        $this->em()->persist($order);
        $this->em()->persist($item);
        $this->em()->flush();
    }

    private function deleteFromAdmin(Product $product): void
    {
        $this->client->loginUser($this->createUser('admin@example.test', ['ROLE_ADMIN']));
        $crawler = $this->client->request('GET', '/admin/products');

        $form = $crawler->filter('form[action="/admin/products/' . $product->getId() . '"]')->form();
        $this->client->submit($form);
    }
}
