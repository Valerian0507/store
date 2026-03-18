<?php

namespace App\Service\Cart;

class CartManager
{
    public function __construct(private CartStorage $cartStorage)
    {
    }

    /**
     * Добавить товар в корзину или увеличить количество.
     * @param int $productId
     * @param int $qty
     * @return void
     */
    public function add(int $productId, int $qty = 1): void
    {
            $qty = max(1, $qty);

            $cart = $this->cartStorage->get();
            $cart[$productId] = ($cart[$productId] ?? 0) + $qty;

            $this->cartStorage->save($cart);
    }

    /**
     * заменить количество
     * @param int $productId
     * @param int $qty
     * @return void
     */
    public function setQuantity(int $productId, int $qty): void
    {
        $cart = $this->cartStorage->get();

        if($qty <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = $qty;
        }
        $this->cartStorage->save($cart);
        //если qty <= 0 → удалить товар
        // иначе поставить новое количество
    }

    public function remove(int $productId): void
    {
        $cart = $this->cartStorage->get();
        unset($cart[$productId]);
        $this->cartStorage->save($cart);

        // удалить товар по id
    }


    /** @return array<int, int> */
    public function getRaw(): array
    {
        return $this->cartStorage->get();
        // вернуть $storage->get()
    }

    public function clear(): void
    {
        $this->cartStorage->clear();
        // просто вызвать $storage->clear()
    }
}
