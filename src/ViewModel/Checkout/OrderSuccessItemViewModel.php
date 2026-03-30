<?php

namespace App\ViewModel\Checkout;

final readonly class OrderSuccessItemViewModel
{
    public function __construct(
        public string $productName,
        public int $quantity,
        public int $unitPriceCents,
        public int $lineTotalCents,
        public ?string $imagePath,
    ) {
    }
}
