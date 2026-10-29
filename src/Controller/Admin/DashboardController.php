<?php

namespace App\Controller\Admin;

use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin', name: 'admin_')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function index(
        ProductRepository $products,
        CategoryRepository $categories,
        OrderRepository $orders,
        UserRepository $users,
    ): Response {
        // Figures shown on the dashboard cards
        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'products' => $products->count([]),
                'outOfStock' => $products->count(['stock' => 0]),
                'categories' => $categories->count([]),
                'orders' => $orders->count([]),
                'pendingOrders' => $orders->count(['status' => 'pending']),
                'users' => $users->count([]),
            ],
        ]);
    }
}
