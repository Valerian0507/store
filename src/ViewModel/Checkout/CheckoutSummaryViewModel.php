<?php

namespace App\ViewModel\Checkout;

final readonly class CheckoutSummaryViewModel
{
    /**
     * @param CheckoutSummaryItemViewModel[] $items
     */
    public function __construct(
        public array $items,
        public int $itemsCount,
        public int $subtotalCents,
        public int $totalCents,
    ) {
    }

    /**
     * Check if the checkout summary view is empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->itemsCount === 0;
    }
}
