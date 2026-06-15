<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\Cart\CartManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart', name: 'app_cart_')]
class CartController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CartManager $cartManager, ProductRepository $productRepository): Response
    {
        $rawCart = $cartManager->getRaw();

        if ($rawCart === []) {
            return $this->render('cart/index.html.twig', [
                'items' => [],
                'totalCents' => 0,
            ]);
        }

        $productIds = array_keys($rawCart);
        $products = $productRepository->findBy(['id' => $productIds]);

        $productsById = [];

        foreach ($products as $product) {
            $productsById[$product->getId()] = $product;
        }

        $items = [];
        $totalCents = 0;

        foreach ($rawCart as $productId => $qty) {
            $product = $productsById[$productId] ?? null;
            if (!$product) {
                continue;
            }

            $unitPriceCents = $product->getPriceCents();
            if ($unitPriceCents === null) {
                continue;
            }

            $lineTotalCents = $unitPriceCents * $qty;

            $items[] = [
                'product' => $product,
                'qty' => $qty,
                'unitPriceCents' => $unitPriceCents,
                'lineTotalCents' => $lineTotalCents,
            ];

            $totalCents += $lineTotalCents;
        }

        return $this->render('cart/index.html.twig', [
            'items' => $items,
            'totalCents' => $totalCents,
        ]);
    }

    #[Route('/add/{id}', name: 'add', methods: ['POST'], requirements: ['id' => '\d+'])]

    public function add(int $id, Request $request, CartManager $cartManager, ProductRepository $productRepository): Response
    {
        $qty = max(1, (int) $request->getPayload()->get('qty', 1));
        $token = (string) $request->getPayload()->get('_token', '');

        if (!$this->isCsrfTokenValid('cart_add_' . $id, $token)) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('app_cart_index');
        }

        $product = $productRepository->find($id);

        if ($product === null) {
            $this->addFlash('danger', 'Produit introuvable.');
            return $this->redirectToRoute('app_cart_index');
        }

        if ($product->getStock() < 1) {
            $this->addFlash('warning', 'Ce produit est en rupture de stock.');
            return $this->redirectToRoute('app_products_index');
        }

        $alreadyInCart = $cartManager->getRaw()[$id] ?? 0;
        if ($alreadyInCart + $qty > $product->getStock()) {
            $this->addFlash('warning', sprintf(
                'Stock insuffisant : il ne reste que %d en stock.',
                $product->getStock()
            ));

            return $this->redirectToRoute('app_products_index');
        }

        $cartManager->add($id, $qty);

        $this->addFlash('success', 'Produit ajouté au panier.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/update/{id}', name: 'update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request, CartManager $cartManager): Response
    {
        $qty = (int) $request->getPayload()->get('qty', 1);
        $token = (string) $request->getPayload()->get('_token', '');

        if (!$this->isCsrfTokenValid('cart_update_' . $id, $token)) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('app_cart_index');
        }

        $cartManager->setQuantity($id, $qty);

        $this->addFlash('success', 'Quantité mise à jour.');

        return $this->redirectToRoute('app_cart_index');
    }


    #[Route('/remove/{id}', name: 'remove', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function remove(int $id, Request $request, CartManager $cartManager): Response
    {

        $token = (string) $request->getPayload()->get('_token', '');

        if (!$this->isCsrfTokenValid('cart_remove_' . $id, $token)) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('app_cart_index');
        }

        $cartManager->remove($id);

        $this->addFlash('success', 'Produit supprimé du panier.');

        return $this->redirectToRoute('app_cart_index');

    }
}


