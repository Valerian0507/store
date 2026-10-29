<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\User;
use App\Form\AddressType;
use App\Repository\AddressRepository;
use App\Repository\NewsletterSubscriberRepository;
use App\Repository\OrderRepository;
use App\Service\Address\AddressManager;
use App\Service\Checkout\OrderSuccessBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Form\ProfileType;
use App\Form\ProfileChangePasswordType;

#[Route('/profile', name: 'app_profile_')]
#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(NewsletterSubscriberRepository $newsletterSubscribers): Response
    {
        $user = $this->getUser();

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'newsletterSubscribed' => $user instanceof User && $newsletterSubscribers->findOneByEmail($user->getEmail()) !== null,
        ]);
    }

    // Orders

    #[Route('/orders', name: 'orders', methods: ['GET'])]
    public function orders(OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();

        if(!$user instanceof User) {
            throw $this->createAccessDeniedException("User is not authenticated.");
        }

        $orders = $orderRepository->findUserOrdersForProfile($user);

        return $this->render('profile/orders/orders.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/orders/{id}', name: 'orders_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function order(OrderSuccessBuilder $orderSuccessBuilder, int $id): Response
    {
        $user = $this->getUser();

        if(!$user instanceof User) {
            throw $this->createAccessDeniedException("User is not authenticated.");
        }

        $orderViewModel = $orderSuccessBuilder->buildForUser($id, $user);

        if ($orderViewModel === null) {
            throw $this->createNotFoundException('Order not found.');
        }

        return $this->render('profile/orders/order_show.html.twig', [
            'order' => $orderViewModel,
        ]);
    }

    #[Route('/orders/{id}/cancel', name: 'orders_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancelOrder(int $id, Request $request, OrderRepository $orderRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException("Utilisateur introuvable.");
        }

        $order = $orderRepository->findOneBy(['id' => $id, 'user' => $user]);

        if ($order === null) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        if (!$this->isCsrfTokenValid('cancel_order_' . $order->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        // Only orders awaiting payment can be cancelled
        if ($order->getStatus() === 'pending') {
            // Put the quantities back in stock
            foreach ($order->getItems() as $item) {
                $product = $item->getProduct();
                if ($product !== null) {
                    $product->setStock($product->getStock() + $item->getQuantity());
                }
            }

            $order->setStatus('cancelled');
            $entityManager->flush();

            $this->addFlash('success', 'Commande annulée, le stock a été remis à jour.');
        }

        return $this->redirectToRoute('app_profile_orders');
    }



// Addresses

    #[Route('/addresses', name: 'addresses', methods: ['GET'])]
    public function addresses(AddressRepository $addressRepository): Response
    {
        $user = $this->getUser();

        if(!$user instanceof User) {
            throw $this->createAccessDeniedException("Utilisateur introuvable.");
        }

        $addresses = $addressRepository->findUserAddressesForProfile($user);


        return $this->render('profile/addresses/addresses.html.twig',[
            'addresses' => $addresses
        ]);
    }


    #[Route('/addresses/new', name: 'addresses_new', methods: ['GET', 'POST'])]
    public function newAddress(Request $request, AddressManager $addressManager): Response
    {
        $user = $this->getUser();

        if(!$user instanceof User){
            throw $this->createAccessDeniedException("Utilisateur introuvable.");
        }

        $address = new Address();

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $addressManager->createForUser($user, $address);

            $this->addFlash('success', 'Adresse ajoutée.');

            return $this->redirectToRoute('app_profile_addresses');
        }

        // 422 when the form is invalid: Turbo only renders the errors with a 4xx status
        return $this->render('profile/addresses/address_form.html.twig', [
            'form' => $form->createView(),
            'pageTitle' => 'Nouvelle adresse',
            'pageSubtitle' => 'Ajoutez une adresse de livraison à votre compte.',
            'submitLabel' => 'Enregistrer l\'adresse',
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200));

    }


    #[Route('/addresses/{id}/edit', name: 'addresses_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editAddress(int $id, Request $request, AddressRepository $addressRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if(!$user instanceof User){
            throw $this->createAccessDeniedException("Utilisateur introuvable.");
        }

        $address = $addressRepository->findOneByIdAndUser($id, $user);

        if ($address === null) {
            throw $this->createNotFoundException('L\'adresse introuvable.');
        }

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $address->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', 'L\'adresse a été modifiée avec succès');

            return $this->redirectToRoute('app_profile_addresses');
        }

        return $this->render('profile/addresses/address_form.html.twig', [
            'form' => $form->createView(),
            'pageTitle' => 'Modifier l\'adresse',
            'pageSubtitle' => 'Mettez à jour votre adresse de livraison.',
            'submitLabel' => 'Enregistrer les modifications',
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200));
    }


    #[Route('/addresses/{id}/delete', name: 'addresses_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteAddress(
        int $id,
        Request $request, AddressRepository $addressRepository, EntityManagerInterface $entityManager, AddressManager $addressManager): Response
        {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not found.');
        }

        $address = $addressRepository->findOneByIdAndUser($id, $user);

        if ($address === null) {
            throw $this->createNotFoundException('Address not found.');
        }

        if (
            !$this->isCsrfTokenValid(
                'delete_address_' . $address->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $wasDefault = $address->isDefault();

        $entityManager->remove($address);
        $entityManager->flush();

        if ($wasDefault) {
            $addressManager->assignNewDefaultAfterDeletion($user);
        }

        $this->addFlash('success', 'Adresse supprimée avec succès.');

        return $this->redirectToRoute('app_profile_addresses');
    }

    #[Route('/addresses/{id}/default', name: 'addresses_set_default', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function setDefaultAddress(int $id, Request $request, AddressRepository $addressRepository, AddressManager $addressManager): Response
    {
        $user = $this->getUser();

        if(!$user instanceof User){
            throw $this->createAccessDeniedException("Utilisateur introuvable.");
        }

        $address = $addressRepository->findOneByIdAndUser($id, $user);

        if($address === null) {
            throw $this->createNotFoundException('Address not found.');
        }

        if (
            !$this->isCsrfTokenValid(
                'set_default_address_' . $address->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $addressManager->setDefaultForUser($user, $address);

        $this->addFlash('success', 'Adresse par défaut mise à jour avec succès.');

        return $this->redirectToRoute('app_profile_addresses');
    }

    // Profile Information and Password

    #[Route('/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException("Utilisateur introuvable.");
        }

        $profileForm = $this->createForm(ProfileType::class, $user);
        $passwordForm = $this->createForm(ProfileChangePasswordType::class);

        $profileForm->handleRequest($request);
        $passwordForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $user->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Vos informations ont été mises à jour.');

            return $this->redirectToRoute('app_profile_edit');
        }

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $newPassword = (string) $passwordForm->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $user->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Mot de passe mis à jour.');

            return $this->redirectToRoute('app_profile_edit');
        }

        return $this->render('profile/profile_information/edit.html.twig', [
            'profileForm' => $profileForm->createView(),
            'passwordForm' => $passwordForm->createView(),
        ]);
    }

}
