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
            if ($unitPriceCents === null || $unitPriceCents <= 0) {
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
            return $this->addResponse($request, 'danger', 'Jeton de sécurité invalide, veuillez recharger la page.');
        }

        $product = $productRepository->find($id);

        if ($product === null) {
            return $this->addResponse($request, 'danger', 'Produit introuvable.');
        }

        if ($product->getStock() < 1) {
            return $this->addResponse($request, 'warning', 'Ce produit est en rupture de stock.');
        }

        $alreadyInCart = $cartManager->getRaw()[$id] ?? 0;
        if ($alreadyInCart + $qty > $product->getStock()) {
            return $this->addResponse($request, 'warning', sprintf(
                'Stock insuffisant : il ne reste que %d en stock.',
                $product->getStock()
            ));
        }

        if ($product->getPriceCents() === null || $product->getPriceCents() <= 0) {
            return $this->addResponse($request, 'warning', 'Ce produit n\'est pas disponible à la vente.');
        }

        $cartManager->add($id, $qty);

        return $this->addResponse($request, 'success', sprintf('« %s » a été ajouté au panier.', $product->getTitle()), $cartManager->countItems());
    }

    /**
     * Add from JS (assets/controllers/cart_add_controller.js): JSON response shown as a notification,
     * with the new item count for the header badge.
     * Without JS: flash message and redirect back to the original page.
     */
    private function addResponse(Request $request, string $type, string $message, ?int $cartCount = null): Response
    {
        if ($request->getPreferredFormat() === 'json') {
            return $this->json(
                ['type' => $type, 'message' => $message, 'cartCount' => $cartCount],
                $type === 'success' ? 200 : 422
            );
        }

        $this->addFlash($type, $message);

        // Referer accepted only when it points to this site (no open redirect)
        $referer = (string) $request->headers->get('referer', '');
        if (str_starts_with($referer, $request->getSchemeAndHttpHost() . '/')) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_products_index');
    }

    #[Route('/update/{id}', name: 'update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request, CartManager $cartManager, ProductRepository $productRepository): Response
    {
        $qty = (int) $request->getPayload()->get('qty', 1);
        $token = (string) $request->getPayload()->get('_token', '');

        if (!$this->isCsrfTokenValid('cart_update_' . $id, $token)) {
            $this->addFlash('danger', 'Jeton de sécurité invalide, veuillez recharger la page.');

            return $this->redirectToRoute('app_cart_index');
        }

        // Same rule as when adding: never more than the available stock
        $stock = $productRepository->find($id)?->getStock() ?? 0;
        if ($qty > $stock) {
            $qty = $stock; // 0: setQuantity removes the product from the cart
            $this->addFlash('warning', $stock > 0
                ? sprintf('Stock insuffisant : quantité ramenée à %d.', $stock)
                : 'Ce produit est en rupture de stock, il a été retiré du panier.');
        } else {
            $this->addFlash('success', 'Quantité mise à jour.');
        }

        $cartManager->setQuantity($id, $qty);

        return $this->redirectToRoute('app_cart_index');
    }


    #[Route('/remove/{id}', name: 'remove', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function remove(int $id, Request $request, CartManager $cartManager): Response
    {

        $token = (string) $request->getPayload()->get('_token', '');

        if (!$this->isCsrfTokenValid('cart_remove_' . $id, $token)) {
            $this->addFlash('danger', 'Jeton de sécurité invalide, veuillez recharger la page.');

            return $this->redirectToRoute('app_cart_index');
        }

        $cartManager->remove($id);

        $this->addFlash('success', 'Produit supprimé du panier.');

        return $this->redirectToRoute('app_cart_index');

    }
}


