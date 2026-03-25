<?php

namespace App\Service\Checkout;

use App\DTO\Checkout\CheckoutSummaryItemView;
use App\DTO\Checkout\CheckoutSummaryView;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\Cart\CartManager;

final class CheckoutSummaryBuilder
{
    public function __construct(
        private CartManager $cartManager,
        private ProductRepository $productRepository,
    ) {
    }

    public function build(): CheckoutSummaryView
    {
        $rawCart = $this->cartManager->getRaw();

        if ($rawCart === []) {
            return new CheckoutSummaryView(
                items: [],
                itemsCount: 0,
                subtotalCents: 0,
                totalCents: 0,
            );
        }

        $productIds = array_map('intval', array_keys($rawCart));

        /** @var Product[] $products */
        $products = $this->productRepository->findBy(['id' => $productIds]);

        $productsById = [];

        foreach ($products as $product) {
            $productId = $product->getId();

            if ($productId !== null) {
                $productsById[$productId] = $product;
            }
        }

        $items = [];
        $itemsCount = 0;
        $subtotalCents = 0;

        foreach ($rawCart as $productId => $qty) {
            $productId = (int) $productId;
            $qty = max(1, (int) $qty);

            $product = $productsById[$productId] ?? null;

            if (!$product instanceof Product) {
                continue;
            }

            $unitPriceCents = $product->getPriceCents();
            $lineSubtotalCents = $unitPriceCents * $qty;

            $items[] = new CheckoutSummaryItemView(
                productId: $productId,
                title: $product->getTitle(),
                unitPriceCents: $unitPriceCents,
                quantity: $qty,
                subtotalCents: $lineSubtotalCents,
                image: $product->getImage(),
            );

            $itemsCount += $qty;
            $subtotalCents += $lineSubtotalCents;
        }

        return new CheckoutSummaryView(
            items: $items,
            itemsCount: $itemsCount,
            subtotalCents: $subtotalCents,
            totalCents: $subtotalCents,
        );
    }
}
