<?php

namespace App\Service\Checkout;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Exception\InsufficientStockException;
use App\Repository\ProductRepository;
use App\Service\Cart\CartManager;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final class CheckoutService
{
    public function __construct(
        private ProductRepository $productRepository,
        private CartManager $cartManager,
        private EntityManagerInterface $em
        ){}

    // Generates a unique reference: ORD-20260419-9F2DF1F5
    private function generateOrderReference(): string
    {
        return 'ORD-' . (new \DateTimeImmutable())->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * Summary of createOrderFromCart
     * @param User $user
     * @param array<string, string> $shippingData
     * @throws LogicException
     * @throws InsufficientStockException
     * @return Order
     */
    public function createOrderFromCart(User $user, array $shippingData): Order
    {
        $rawCart = $this->cartManager->getRaw();
        if($rawCart === []) {
            throw new LogicException('Cannot create an order from an empty cart.');
            }

        $productIds = array_keys($rawCart);
        $products = $this->productRepository->findBy(['id' => $productIds]);
        $productsById = [];

        foreach ($products as $product) {
            $productsById[$product->getId()] = $product;
        }

        $order = new Order();
        $order->setUser($user);
        $order->setStatus('pending');
        $order->setReference($this->generateOrderReference());

        // Shipping data copied into the order
        $order->setShippingFirstName($shippingData['firstName']);
        $order->setShippingLastName($shippingData['lastName']);
        $order->setShippingStreet($shippingData['street']);
        $order->setShippingCity($shippingData['city']);
        $order->setShippingPostalCode($shippingData['postalCode']);
        $order->setShippingCountry($shippingData['country']);

        foreach ($rawCart as $productId => $qty) {
            $product = $productsById[$productId] ?? null;

            // 3 checks
            // Check 1: does the product exist?
            if(!$product) {
                throw new LogicException(sprintf('Product not found for id %d.', $productId));
            }

            // Check 2: is the price set?
            if($product->getPriceCents() === null || $product->getPriceCents() <= 0) {
                throw new LogicException(sprintf('No price for product #%d.', $productId));
            }

            // Check 3: is the stock sufficient? (the message is shown to the customer)
            if($product->getStock() < $qty){
                throw new InsufficientStockException(sprintf('Stock insuffisant pour "%s".', $product->getTitle()));
            }

            // Snapshot: product data copied at order time
            $orderItem = OrderItem::fromProduct($product, $qty);
            $order->addItem($orderItem);

            $product->setStock($product->getStock() - $qty); // Decrement the product stock
        }

        $order->recalcTotal();

        $this->em->persist($order);
        $this->em->flush(); // Everything in a single transaction
        $this->cartManager->clear(); // Empty the cart after the order

        return $order;
    }
}
