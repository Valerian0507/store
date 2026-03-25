<?php

namespace App\DTO\Checkout;

final readonly class CheckoutSummaryItemView
{
    public function __construct(
        public int $productId,
        public string $title,
        public int $unitPriceCents,
        public int $quantity,
        public int $subtotalCents,
        public ?string $image,
    ) {
    }
}
