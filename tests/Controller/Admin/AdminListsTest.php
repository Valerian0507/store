<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Order;
use App\Entity\User;
use App\Tests\Support\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Administration: access restricted to ROLE_ADMIN and list filters
 * (orders by status / search, users, products by category / search).
 */
class AdminListsTest extends WebTestCase
{
    use DatabaseTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->resetDatabase();
    }

    public function testRegularUserIsForbidden(): void
    {
        $this->client->loginUser($this->createUser('client@example.test'));

        $this->client->request('GET', '/admin');

        self::assertResponseStatusCodeSame(403);
    }

    public function testOrdersCanBeFilteredByStatusAndSearch(): void
    {
        $customer = $this->createUser('acheteur@example.test');
        $this->createOrder($customer, 'ORD-TEST-PAID', 'paid');
        $this->createOrder($customer, 'ORD-TEST-PENDING', 'pending');
        $this->loginAsAdmin();

        $this->client->request('GET', '/admin/orders', ['status' => 'paid']);
        self::assertResponseIsSuccessful();
        $this->assertPageHas('ORD-TEST-PAID', 'ORD-TEST-PENDING');

        $this->client->request('GET', '/admin/orders', ['search' => 'PENDING']);
        $this->assertPageHas('ORD-TEST-PENDING', 'ORD-TEST-PAID');

        // Unknown status: filter ignored, all orders
        $this->client->request('GET', '/admin/orders', ['status' => 'inconnu']);
        $this->assertPageHas('ORD-TEST-PAID');
        $this->assertPageHas('ORD-TEST-PENDING');
    }

    public function testUsersCanBeSearchedAndFilteredByVerification(): void
    {
        $this->createUser('confirme@example.test');
        $this->createUser('enattente@example.test')->setIsVerified(false);
        $this->em()->flush();
        $this->loginAsAdmin();

        $this->client->request('GET', '/admin/users', ['verified' => 'no']);
        self::assertResponseIsSuccessful();
        $this->assertPageHas('enattente@example.test', 'confirme@example.test');

        $this->client->request('GET', '/admin/users', ['q' => 'confirme']);
        $this->assertPageHas('confirme@example.test', 'enattente@example.test');
    }

    public function testProductsCanBeFilteredByCategoryAndSearch(): void
    {
        $pool = $this->createProduct('P-001', 'Piscine ronde de test', category: 'piscines');
        $this->createProduct('S-001', 'Spa carré de test', category: 'spa');
        $this->loginAsAdmin();

        $this->client->request('GET', '/admin/products', ['category' => $pool->getCategory()?->getId()]);
        self::assertResponseIsSuccessful();
        $this->assertPageHas('Piscine ronde de test', 'Spa carré de test');

        $this->client->request('GET', '/admin/products', ['q' => 'S-001']);
        $this->assertPageHas('Spa carré de test', 'Piscine ronde de test');
    }

    private function loginAsAdmin(): void
    {
        $this->client->loginUser($this->createUser('admin@example.test', ['ROLE_ADMIN']));
    }

    private function createOrder(User $user, string $reference, string $status): void
    {
        $order = (new Order())
            ->setUser($user)
            ->setReference($reference)
            ->setStatus($status)
            ->setTotalCents(4990);

        $this->em()->persist($order);
        $this->em()->flush();
    }

    // The page contains $expected and not $unexpected
    private function assertPageHas(string $expected, ?string $unexpected = null): void
    {
        $content = $this->client->getCrawler()->filter('main, .admin-main, body')->first()->text();

        self::assertStringContainsString($expected, $content);
        if ($unexpected !== null) {
            self::assertStringNotContainsString($unexpected, $content);
        }
    }
}
