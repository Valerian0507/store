<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Tests\Support\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Cart: JSON add ("Ajouter au panier" button without reload),
 * stock checks when adding and when updating the quantity.
 */
class CartControllerTest extends WebTestCase
{
    use DatabaseTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->resetDatabase();
    }

    public function testJsonAddReturnsMessageAndCartCount(): void
    {
        $product = $this->createProduct('T-100', 'Piscine de test', stock: 5);

        $this->addToCart($product, 2);

        self::assertResponseIsSuccessful();
        $data = $this->json();
        self::assertSame('success', $data['type']);
        self::assertSame(2, $data['cartCount']);
        self::assertStringContainsString('Piscine de test', $data['message']);
    }

    public function testJsonAddAboveStockIsRefused(): void
    {
        $product = $this->createProduct('T-101', 'Spa de test', stock: 2);

        $this->addToCart($product, 3);

        self::assertResponseStatusCodeSame(422);
        $data = $this->json();
        self::assertSame('warning', $data['type']);
        self::assertStringContainsString('il ne reste que 2', $data['message']);
        self::assertNull($data['cartCount']);
    }

    public function testJsonAddOutOfStockProductIsRefused(): void
    {
        // Stock drops to 0 between showing the product page and the click (the page hides the button at 0)
        $product = $this->createProduct('T-102', 'Matelas épuisé', stock: 1);
        $token = $this->addToken($product);
        $this->em()->getConnection()->executeStatement('UPDATE products SET stock = 0 WHERE id = ?', [$product->getId()]);

        $this->postAdd($product, 1, $token);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('rupture de stock', $this->json()['message']);
    }

    public function testJsonAddWithInvalidTokenIsRefused(): void
    {
        $product = $this->createProduct('T-103', 'Produit protégé');

        $this->postAdd($product, 1, 'jeton-invalide');

        self::assertResponseStatusCodeSame(422);
        self::assertSame('danger', $this->json()['type']);
    }

    public function testUpdateQuantityIsCappedAtStock(): void
    {
        $product = $this->createProduct('T-104', 'Piscine limitée', stock: 3);
        $this->addToCart($product, 1);

        $crawler = $this->client->request('GET', '/cart');
        $form = $crawler->filter('form[action="/cart/update/' . $product->getId() . '"]')->form(['qty' => 10]);
        $this->client->submit($form);

        self::assertResponseRedirects('/cart');
        $crawler = $this->client->followRedirect();

        self::assertStringContainsString('quantité ramenée à 3', $crawler->filter('body')->text());
        self::assertSame('3', $crawler->filter('#qty-' . $product->getId())->attr('value'));
    }

    private function addToCart(Product $product, int $qty): void
    {
        $this->postAdd($product, $qty, $this->addToken($product));
    }

    // CSRF token read on the product page, as the browser does
    private function addToken(Product $product): string
    {
        $crawler = $this->client->request('GET', '/products/' . $product->getId());

        return (string) $crawler->filter('form[action="/cart/add/' . $product->getId() . '"] input[name="_token"]')->attr('value');
    }

    // Request sent by assets/controllers/cart_add_controller.js (JSON response)
    private function postAdd(Product $product, int $qty, string $token): void
    {
        $this->client->request('POST', '/cart/add/' . $product->getId(), ['qty' => $qty, '_token' => $token], [], ['HTTP_ACCEPT' => 'application/json']);
    }

    /** @return array{type: string, message: string, cartCount: ?int} */
    private function json(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }
}
