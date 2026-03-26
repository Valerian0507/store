<?php

namespace App\Service\Checkout;

use App\Entity\User;
use App\Repository\OrderRepository;
use App\ViewModel\Checkout\OrderSuccessItemViewModel;
use App\ViewModel\Checkout\OrderSuccessViewModel;

final class OrderSuccessBuilder
{
    public function __construct(
        private OrderRepository $orderRepository,
    ) {
    }

    public function buildForUser(int $orderId, User $user): ?OrderSuccessViewModel
    {
        $order = $this->orderRepository->findOneForSuccessPage($orderId, $user);

        if ($order === null) {
            return null;
        }

        $items = [];
        $subtotalCents = 0;

        foreach ($order->getItems() as $item) {
            $quantity = $item->getQuantity();
            $unitPriceCents = $item->getUnitPriceCents();
            $lineTotalCents = $quantity * $unitPriceCents;

            $subtotalCents += $lineTotalCents;

            $items[] = new OrderSuccessItemViewModel(
                productName: $item->getProductName(),
                quantity: $quantity,
                unitPriceCents: $unitPriceCents,
                lineTotalCents: $lineTotalCents,
                imagePath: null, // потом подставишь snapshot image, если добавишь
            );
        }

        $shippingCents = 0;

        $shippingFullName = trim(
            ($order->getShippingFirstName() ?? '') . ' ' . ($order->getShippingLastName() ?? '')
        );

        $statusLabel = match ($order->getStatus()) {
            'pending' => 'Pending Payment',
            'paid' => 'Paid',
            'shipped' => 'Shipped',
            'cancelled' => 'Cancelled',
            default => ucfirst($order->getStatus()),
        };

        return new OrderSuccessViewModel(
            orderId: $order->getId(),
            status: $order->getStatus(),
            statusLabel: $statusLabel,
            subtotalCents: $subtotalCents,
            shippingCents: $shippingCents,
            totalCents: $order->getTotalCents(),
            shippingFullName: $shippingFullName,
            shippingStreet: (string) $order->getShippingStreet(),
            shippingPostalCode: (string) $order->getShippingPostalCode(),
            shippingCity: (string) $order->getShippingCity(),
            shippingCountry: (string) $order->getShippingCountry(),
            items: $items,
        );
    }
}
