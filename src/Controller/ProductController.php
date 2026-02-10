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

        $category = $request->query->get('category');
        $category = is_string($category) && $category !== '' ? $category : null;

        $perPage = (int) $this->getParameter('catalog.per_page');

        $categories = $products->findAllCategories();
        $sort = (string) $request->query->get('sort', 'newest');

        $allowedSorts = ['newest', 'price_asc', 'price_desc', 'title_asc', 'title_desc'];

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'newest';
        }

        $q = $request->query->get('q');
        $q = is_string($q) ? trim($q) : null;
        $q = $q !== '' ? $q : null;


        if($q != null && mb_strlen($q) > 80){
            $q = mb_strlen($q, 0, 80);
        }

        $total = $products->countForCatalog($category, $q);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min ($page, $pages);

        $items = $products->findForCatalog(page: $page, perPage: $perPage, category: $category, sort: $sort, q: $q);

        $window = 2;

        $start = max(1, $page - $window);
        $end   = min($pages, $page + $window);

        $pagination = [
            'current' => $page,
            'pages' => $pages,
            'start' => $start,
            'end' => $end,
            'prev' => $page > 1,
            'next' => $page < $pages,
        ];




        return $this->render('product/index.html.twig', [
            'products' => $items,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'perPage' => $perPage,
            'category' => $category,
            'categories' => $categories,
            'sort' => $sort,
            'q' => $q,
            'pagination' => $pagination,

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

