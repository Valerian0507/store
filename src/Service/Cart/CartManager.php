<?php

namespace App\Service\Cart;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\User;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class CartManager
{
    public function __construct(
        private CartStorage $cartStorage,
        private Security $security,
        private CartRepository $cartRepository,
        private ProductRepository $productRepository,
        private EntityManagerInterface $em,)
    {
    }
    /**
     * CartManager
     * - add
     * - getRaw
     * - setQuantity
     * - remove
     * - clear
     * - mergeSessionCartIntoUserCart
     * - CartLoginSubscriber
     * - listening to LoginSuccessEvent
     * - calls CartManager
     */

    /**
     * @param int $productId
     * @param int $qty
     * @return void
     */
    public function add(int $productId, int $qty = 1): void
    {
        $qty = max(1, $qty);

        $user = $this->security->getUser();

        if(!$user instanceof User) {
            // Visiteur — stockage en session via CartStorage
            $cart = $this->cartStorage->get();
            $cart[$productId] = ($cart[$productId] ?? 0) + $qty;

            $this->cartStorage->save($cart);

            return;
        }

        $product = $this->productRepository->find($productId);

        if(!$product) {
            throw new \LogicException("Produit n'est pas trouvé");
        }

        $cart = $this->getOrCreateCart($user);

        $existingItem = $this->findCartItemByProductId($cart, $productId);

        if($existingItem !== null){
            $existingItem->setQuantity($existingItem->getQuantity() + $qty);
        } else {
            $cartItem = new CartItem();

            $cartItem->setCart($cart);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($qty);

            $cart->addCartItem($cartItem);

            $this->em->persist($cartItem);
        }

        $cart->setUpdatedAt(new \DateTimeImmutable());

        $this->em->persist($cart);
        $this->em->flush();
    }

    /** @return array<int, int> */
    public function getRaw(): array
    {
        $user = $this->security->getUser();

        if(!$user instanceof User) {
            return $this->cartStorage->get();
        }

        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if(!$cart){
            return[];
        }

        $raw = [];

        foreach ($cart->getCartItems() as $item) {
            $product = $item->getProduct();

            if ($product === null || $product->getId() === null) {
                continue;
            }

            $raw[$product->getId()] = $item->getQuantity();
        }

        return $raw;
    }



    /**
     * @param int $productId
     * @param int $qty
     * @return void
     */
    public function setQuantity(int $productId, int $qty): void
    {
        $qty = max(0, $qty);

        $user = $this->security->getUser();

        if(!$user instanceof User) {
            $cart = $this->cartStorage->get();

            if($qty <= 0) {
                unset($cart[$productId]);
            } else {
                $cart[$productId] = $qty;
            }

            $this->cartStorage->save($cart);

            return;
        }

        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if(!$cart){
            return;
        }

        $item = $this->findCartItemByProductId($cart, $productId);

        if(!$item){
            return;
        }

        if ($qty <= 0) {
            $cart->removeCartItem($item);
            $this->em->remove($item);
        } else {
            $item->setQuantity($qty);
        }

        $cart->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

    }

    public function remove(int $productId): void
    {
        $user = $this->security->getUser();

        if(!$user instanceof User){
            $cart = $this->cartStorage->get();
            unset($cart[$productId]);
            $this->cartStorage->save($cart);

            return;
        }

        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if(!$cart){
            return;
        }

        $item = $this->findCartItemByProductId($cart, $productId);

        if(!$item){
            return;
        }

        $cart->removeCartItem($item);
        $this->em->remove($item);

        $cart->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();
    }


    public function clear(): void
    {
        $user = $this->security->getUser();

        if(!$user instanceof User){
            $this->cartStorage->clear();
            return;
        }

        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if(!$cart){
            return;
        }

        foreach ($cart->getCartItems()->toArray() as $item) {
            $cart->removeCartItem($item);
            $this->em->remove($item);
        }

        $cart->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();
    }

    public function mergeSessionCartIntoUserCart(User $user): void
    {
        $sessionCart = $this->cartStorage->get();

        if(empty($sessionCart)) {
            return;
        }

        $cart = $this->getOrCreateCart($user);

        foreach($sessionCart as $productId => $qty){
            $product = $this->productRepository->find((int) $productId);

            if(!$product){
                continue;
            }

            $qty = max(1, (int) $qty);

            $existingItem = $this->findCartItemByProductId($cart, (int) $productId);

            if ($existingItem !== null) {
                $existingItem->setQuantity($existingItem->getQuantity() + $qty);
            } else {
                $cartItem = new CartItem();
                $cartItem->setProduct($product);
                $cartItem->setCart($cart);
                $cartItem->setQuantity($qty);

                $cart->addCartItem($cartItem);
                $this->em->persist($cartItem);
            }
        }

        $cart->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($cart);
        $this->em->flush();

        $this->cartStorage->clear();

    }

    private function getOrCreateCart(User $user): Cart
    {
        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if ($cart instanceof Cart) {
            return $cart;
        }

        $cart = new Cart();
        $cart->setUser($user);

        $this->em->persist($cart);

        return $cart;
    }

    private function findCartItemByProductId(Cart $cart, int $productId): ?CartItem
    {
        foreach ($cart->getCartItems() as $item) {
            $product = $item->getProduct();

            if ($product !== null && $product->getId() === $productId) {
                return $item;
            }
        }

        return null;
    }


}
