<?php

namespace App\Twig;

use App\Service\Cart\CartManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Nombre d'articles du panier, affiché sur l'icône panier de l'en-tête.
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
