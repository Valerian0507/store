<?php

namespace App\ViewModel\Checkout;

final readonly class OrderSuccessViewModel
{
    /**
     * @param OrderSuccessItemViewModel[] $items
     */
    public function __construct(
        public int $orderId,
        public string $status,
        public string $statusLabel,
        public int $subtotalCents,
        public int $shippingCents,
        public int $totalCents,
        public string $shippingFullName,
        public string $shippingStreet,
        public string $shippingPostalCode,
        public string $shippingCity,
        public string $shippingCountry,
        public array $items,
    ) {
    }
}
