<?php

namespace App\Service\Checkout;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
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

    // Génère une référence unique : ORD-20260419-9F2DF1F5
    private function generateOrderReference(): string
    {
        return 'ORD-' . (new \DateTimeImmutable())->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * Summary of createOrderFromCart
     * @param User $user
     * @param array $shippingData
     * @throws LogicException
     * @return Order
     */
    public function createOrderFromCart(User $user, array $shippingData): Order
    {
        $rawCart = $this->cartManager->getRaw();
        if($rawCart === []) {
            throw new LogicException("Impossible de créer une commande à partir d'un panier vide.");
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

        // Données d'expédition copiées dans la commande
        $order->setShippingFirstName($shippingData['firstName']);
        $order->setShippingLastName($shippingData['lastName']);
        $order->setShippingStreet($shippingData['street']);
        $order->setShippingCity($shippingData['city']);
        $order->setShippingPostalCode($shippingData['postalCode']);
        $order->setShippingCountry($shippingData['country']);

        foreach ($rawCart as $productId => $qty) {
            $product = $productsById[$productId] ?? null;

            if(!$product) {
                throw new LogicException(sprintf("Produit introuvable pour l'id %d.", $productId));
            }

            if($product->getPriceCents() === null) {
                throw new LogicException(sprintf("Prix introuvable pour le produit #%d.", $productId));
            }

            // Snapshot : copie des données produit au moment de la commande
            $orderItem = OrderItem::fromProduct($product, $qty);
            $order->addItem($orderItem);
        }

        $order->recalcTotal();

        $this->em->persist($order);
        $this->em->flush(); // Tout en une seule transaction
        $this->cartManager->clear(); // Vide le panier après commande

        return $order;
    }
}
