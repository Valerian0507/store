<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\AddressRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/users', name: 'admin_users_')]
class UserController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $verifiedParam = (string) $request->query->get('verified', '');
        $page = max(1, $request->query->getInt('page', 1));

        $limit = 10;
        $offset = ($page - 1) * $limit;

        $verified = match ($verifiedParam) {
            'yes' => true,
            'no' => false,
            default => null,
        };

        $users = $userRepository->findUsersForAdminList(
            $search !== '' ? $search : null,
            $verified,
            $limit,
            $offset
        );

        $totalUsers = $userRepository->countForAdminList(
            $search !== '' ? $search : null,
            $verified
        );

        $totalPages = max(1, (int) ceil($totalUsers / $limit));

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'search' => $search,
            'verifiedFilter' => $verifiedParam,
            'page' => $page,
            'limit' => $limit,
            'totalUsers' => $totalUsers,
            'totalPages' => $totalPages,
        ]);
    }


    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, UserRepository $userRepository, OrderRepository $orderRepository, AddressRepository $addressRepository): Response
    {
        $user = $userRepository->findOneForAdminShow($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $orders = $orderRepository->findRecentOrdersForUserForAdminShow($user, 5);

        $addresses = $addressRepository->findUserAddressesForProfile($user);

        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
            'orders' => $orders,
            'addresses' => $addresses,
        ]);
    }

    #[Route('/{id}/role', name: 'update_role', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateRole(int $id, UserRepository $userRepository, Request $request, EntityManagerInterface $em): Response
    {
        $user = $userRepository->findOneForAdminShow($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        if (
            !$this->isCsrfTokenValid(
                'update_user_role_' . $user->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            $this->addFlash('danger', 'Invalid CSRF token.');

            return $this->redirectToRoute('admin_users_show', [
                'id' => $user->getId(),
            ]);
        }

        $role = (string) $request->request->get('role');
        $allowedRoles = ['ROLE_USER', 'ROLE_ADMIN'];

        if (!in_array($role, $allowedRoles, true)) {
            $this->addFlash('danger', 'Rôle invalide.');

            return $this->redirectToRoute('admin_users_show', [
                'id' => $user->getId(),
            ]);
        }

        $currentUser = $this->getUser();

        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            $this->addFlash('danger', 'Vous ne pouvez pas modifier votre propre rôle.');

            return $this->redirectToRoute('admin_users_show', [
                'id' => $user->getId(),
            ]);
        }

        $user->setRoles([$role]);
        $user->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'Le rôle de l’utilisateur a été mis à jour.');

        return $this->redirectToRoute('admin_users_show', [
            'id' => $user->getId(),
        ]);
    }
}
