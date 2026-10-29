<?php

namespace App\Tests\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Service\Payment\StripeCheckoutService;
use App\Tests\Support\DatabaseTestTrait;
use Stripe\Checkout\Session;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Return from Stripe (CheckoutController::confirm): the order becomes "paid" and the confirmation
 * email is sent only once. Stripe is replaced by a fake service (no network call).
 */
class CheckoutConfirmTest extends WebTestCase
{
    use DatabaseTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Keep the same container between requests, hence the fake Stripe service
        $this->client->disableReboot();
        $this->resetDatabase();
    }

    public function testPaidSessionMarksOrderPaidAndSendsEmailOnce(): void
    {
        $this->fakeStripe('paid');
        [$user, $order] = $this->createPendingOrder();
        $this->client->loginUser($user);

        $this->client->request('GET', '/checkout/confirm/' . $order->getId(), ['session_id' => 'cs_test_123']);

        self::assertResponseRedirects('/checkout/success/' . $order->getId());
        self::assertSame('paid', $this->statusOf($order));
        self::assertEmailCount(1);

        $email = self::getMailerMessage();
        self::assertNotNull($email);
        self::assertEmailAddressContains($email, 'to', 'acheteur@example.test');
        self::assertEmailSubjectContains($email, 'Votre commande ORD-TEST-0001 est confirmée');
        self::assertEmailHtmlBodyContains($email, 'Piscine de test');

        // Return replayed (page reload): order already paid, no second email
        $this->client->request('GET', '/checkout/confirm/' . $order->getId(), ['session_id' => 'cs_test_123']);
        self::assertEmailCount(0);
    }

    public function testUnpaidSessionKeepsOrderPendingWithoutEmail(): void
    {
        $this->fakeStripe('unpaid');
        [$user, $order] = $this->createPendingOrder();
        $this->client->loginUser($user);

        $this->client->request('GET', '/checkout/confirm/' . $order->getId(), ['session_id' => 'cs_test_123']);

        self::assertResponseRedirects('/checkout/success/' . $order->getId());
        self::assertSame('pending', $this->statusOf($order));
        self::assertEmailCount(0);
    }

    public function testOrderOfAnotherUserIsNotFound(): void
    {
        $this->fakeStripe('paid');
        [, $order] = $this->createPendingOrder();
        $this->client->loginUser($this->createUser('autre@example.test'));

        $this->client->request('GET', '/checkout/confirm/' . $order->getId(), ['session_id' => 'cs_test_123']);

        self::assertResponseStatusCodeSame(404);
        self::assertSame('pending', $this->statusOf($order));
    }

    private function fakeStripe(string $paymentStatus): void
    {
        static::getContainer()->set(StripeCheckoutService::class, new class($paymentStatus) extends StripeCheckoutService {
            public function __construct(private readonly string $paymentStatus)
            {
                parent::__construct('sk_test_fake');
            }

            public function retrieveCheckoutSession(string $sessionId): Session
            {
                return Session::constructFrom(['id' => $sessionId, 'payment_status' => $this->paymentStatus]);
            }
        });
    }

    /** @return array{User, Order} */
    private function createPendingOrder(): array
    {
        $user = $this->createUser('acheteur@example.test');
        $product = $this->createProduct('T-200', 'Piscine de test', stock: 4, priceCents: 24900);

        $item = (new OrderItem())
            ->setProduct($product)
            ->setProductName('Piscine de test')
            ->setUnitPriceCents(24900)
            ->setQuantity(1);

        $order = (new Order())
            ->setUser($user)
            ->setReference('ORD-TEST-0001')
            ->setTotalCents(24900)
            ->setShippingFirstName('Camille')
            ->setShippingLastName('Martin')
            ->setShippingStreet('12 rue des Oliviers')
            ->setShippingPostalCode('13100')
            ->setShippingCity('Aix-en-Provence')
            ->setShippingCountry('France');
        $order->addItem($item);

        $this->em()->persist($order);
        $this->em()->persist($item);
        $this->em()->flush();

        return [$user, $order];
    }

    private function statusOf(Order $order): string
    {
        return (string) $this->em()->getConnection()->fetchOne('SELECT status FROM orders WHERE id = ?', [$order->getId()]);
    }
}
