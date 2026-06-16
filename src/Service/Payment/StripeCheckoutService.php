<?php

namespace App\Service\Payment;

use App\Entity\Order;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

final class StripeCheckoutService
{
    private StripeClient $stripeClient;

    public function __construct(string $stripeApiKey)
    {
        $this->stripeClient = new StripeClient($stripeApiKey);
    }

    public function createStripeCheckoutSesion(Order $order, string $successUrl, string $cancelUrl): Session
    {
        return $this->stripeClient->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $order->getTotalCents(),
                    'product_data' => ['name' => 'Commande' . $order->getReference()],
                ],
                'quantity' => 1,
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => ['order_id' => (string) $order->getId()],
        ]);
    }

    public function retrieveCheckoutSession(string $sessionId): Session
    {
        return $this->stripeClient->checkout->sessions->retrieve($sessionId);
    }

}
