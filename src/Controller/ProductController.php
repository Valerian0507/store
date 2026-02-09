<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('product', name: 'app_product', methods: ['GET'])]
    public function index(Request $request, ProductRepository $products): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = (int) $this->getParameter('catalog.per_page');

        $items = $products->findForCatalog(page: $page, perPage: $perPage);

//        $items = $products->findForCatalog(page: $page, perPage: 20);

        return $this->render('product/index.html.twig', [
            'products' => $items,
            'page' => $page,
        ]);
    }

    #[Route('products/{id}', name: 'app_product_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, ProductRepository $products): Response
    {
        $product = $products->findOneForCatalog($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}

