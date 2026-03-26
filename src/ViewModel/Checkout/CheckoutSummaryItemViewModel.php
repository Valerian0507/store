<?php

namespace App\ViewModel\Checkout;

final readonly class CheckoutSummaryItemViewModel
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
