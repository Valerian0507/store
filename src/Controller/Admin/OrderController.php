<?php

namespace App\Controller\Admin;

use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/orders', name: 'admin_orders_')]
class OrderController extends AbstractController
{
    private const STATUS_LABELS = [
        'pending' => 'En attente de paiement',
        'paid' => 'Payée',
        'shipped' => 'Expédiée',
        'cancelled' => 'Annulée',
    ];

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository, Request $request): Response
    {
        $selectedStatus = $request->query->get('status');
        $selectedStatus = is_string($selectedStatus) ? trim($selectedStatus) : null;
        $selectedStatus = $selectedStatus !== '' ? $selectedStatus : null;

        if ($selectedStatus !== null && !array_key_exists($selectedStatus, self::STATUS_LABELS)) {
            $selectedStatus = null;
        }

        $search = $request->query->get('search');
        $search = is_string($search) ? trim($search) : null;
        $search = $search !== '' ? $search : null;

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $totalOrders = $orderRepository->countForAdminList($selectedStatus, $search);
        $totalPages = max(1, (int) ceil($totalOrders / $limit));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $limit;
        }

        $orders = $orderRepository->findOrdersForAdminList($selectedStatus, $search, $limit, $offset);

        return $this->render('admin/orders/index.html.twig', [
            'orders' => $orders,
            'selectedStatus' => $selectedStatus,
            'search' => $search,
            'statusLabels' => self::STATUS_LABELS,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->findOneForAdminShow($id);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        return $this->render('admin/orders/show.html.twig', [
            'order' => $order,
            'statusLabels' => self::STATUS_LABELS,
        ]);

    }

    #[Route('/{id}/status', name: 'update_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateStatus(int $id, Request $request, OrderRepository $orderRepository, EntityManagerInterface $entityManager): Response
    {
        $order = $orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        if (!$this->isCsrfTokenValid('admin_order_status_' . $order->getId(),
            (string) $request->request->get('_token')
            )
        ) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('admin_orders_show', [
                'id' => $order->getId(),
            ]);
        }

        $newStatus = (string) $request->request->get('status');
        $currentStatus = $order->getStatus();

        if (!array_key_exists($newStatus, self::STATUS_LABELS)) {
            $this->addFlash('danger', 'Statut invalide.');

            return $this->redirectToRoute('admin_orders_show', [
                'id' => $order->getId(),
            ]);
        }


        if ($newStatus !== $currentStatus) {
            $order->setStatus($newStatus);
            $order->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Le statut de la commande a été modifié.');
        } else {
            $this->addFlash('info', 'Le statut est déjà celui-ci.');
        }


        return $this->redirectToRoute('admin_orders_show', [
            'id' => $order->getId(),
        ]);
    }
}
