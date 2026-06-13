<?php

namespace App\Service\Cart;

use LogicException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartStorage
{
    private const CART_KEY = 'cart';
    public function __construct(private RequestStack $requestStack)
    {
    }

    /** @return array<int, int> */
    public function get(): array
    {
        $raw = $this->getSession()->get(self::CART_KEY, []);

        if(!is_array($raw)) {
            return [];
            }

        $cart = [];

        foreach ($raw as $productId => $qty) {
            $pid = (int) $productId;
            $q = (int) $qty;

            if ($pid > 0 && $q > 0) {
                $cart[$pid] = $q;
            }
        }
        return $cart;
    }

    /** @param array<int, int> $cart */
    public function save(array $cart): void
    {
        $cleanCart = [];

        foreach ($cart as $productId => $qty) {
            $pid = (int) $productId;
            $q = (int) $qty;

            if($pid > 0 && $q > 0) {
               $cleanCart[$pid] = $q;
            }
        }

        if ($cleanCart === []) {
            $this->getSession()->remove(self::CART_KEY);
                return;
        }

        $this->getSession()->set(self::CART_KEY, $cleanCart);
    }

    public function clear(): void
    {
        $this->getSession()->remove(self::CART_KEY);
        // remove the cart key from the session
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }
}
