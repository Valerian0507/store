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
                imagePath: $item->getProductImage(), // snapshot image
            );
        }

        $shippingCents = 0;

        $shippingFullName = trim(
            ($order->getShippingFirstName() ?? '') . ' ' . ($order->getShippingLastName() ?? '')
        );

        $statusLabel = match ($order->getStatus()) {
            'pending' => 'En attente de paiement',
            'paid' => 'Payée',
            'cancelled' => 'Annulée',
            'shipped' => 'Expédiée',
            default => ucfirst($order->getStatus()),
        };

        $statusBadgeClass = match ($order->getStatus()) {
            'pending' => 'bg-warning text-dark',
            'paid' => 'bg-success text-white',
            'shipped' => 'bg-primary text-white',
            'cancelled' => 'bg-danger text-white',
            default => 'bg-secondary text-white',
        };

        return new OrderSuccessViewModel(
            orderId: (int) $order->getId(),
            reference: (string) $order->getReference(),
            status: $order->getStatus(),
            statusLabel: $statusLabel,
            statusBadgeClass: $statusBadgeClass,
            createdAt: $order->getCreatedAt(),
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
