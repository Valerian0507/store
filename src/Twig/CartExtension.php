<?php

namespace App\Twig;

use App\Service\Cart\CartManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Number of items in the cart, shown on the cart icon of the header.
 */
class CartExtension extends AbstractExtension
{
    public function __construct(private CartManager $cartManager)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cart_count', [$this->cartManager, 'countItems']),
        ];
    }
}
