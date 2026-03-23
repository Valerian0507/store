<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/products', name: 'app_products_index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $products, CategoryRepository $categoriesRepo): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));

        $categoryId = $request->query->filter('category', null, FILTER_VALIDATE_INT, [
            'flags' => FILTER_NULL_ON_FAILURE,
            'options' => ['min_range' => 1],
        ]);

        $perPage = (int) $this->getParameter('catalog.per_page');

        $categories = $categoriesRepo->findAllOrdered();
        $sort = (string) $request->query->get('sort', 'newest');

        $allowedSorts = ['newest', 'price_asc', 'price_desc', 'title_asc', 'title_desc'];

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'newest';
        }

        $q = $request->query->get('q');
        $q = is_string($q) ? trim($q) : null;
        $q = $q !== '' ? $q : null;


        if ($q !== null && mb_strlen($q) > 80) {
            $q = mb_substr($q, 0, 80);
        }

        $total = $products->countForCatalog($categoryId, $q);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min ($page, $pages);

        $items = $products->findForCatalog(page: $page, perPage: $perPage, category: $categoryId, sort: $sort, q: $q);

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
            'category' => $categoryId,
            'categories' => $categories,
            'sort' => $sort,
            'q' => $q,
            'pagination' => $pagination,

        ]);
    }

    #[Route('/products/{id}', name: 'app_products_show', requirements: ['id' => '\d+'], methods: ['GET'])]
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

