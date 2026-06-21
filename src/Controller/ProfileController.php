<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\User;
use App\Form\AddressType;
use App\Repository\AddressRepository;
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
use App\Form\ChangePasswordType;

#[Route('/profile', name: 'app_profile_')]
#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

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
            throw $this->createNotFoundException('Commande non trouvée.');
        }

        return $this->render('profile/orders/order_show.html.twig', [
            'order' => $orderViewModel,
        ]);
    }




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

            $this->addFlash('success', 'Adresse ajouté');

            return $this->redirectToRoute('app_profile_addresses');
        }

        return $this->render('profile/addresses/address_form.html.twig', [
            'form' => $form->createView(),
            'pageTitle' => 'Nouvelle adresse',
            'submitLabel' => 'Sauvegarder l\'adresse',

        ]);

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
            'submitLabel' => 'Mettre à jour',
        ]);
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

    #[Route('/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException("Utilisateur introuvable.");
        }

        $profileForm = $this->createForm(ProfileType::class, $user);
        $passwordForm = $this->createForm(ChangePasswordType::class);

        $profileForm->handleRequest($request);
        $passwordForm->handleRequest($request);

        // Форма имени/фамилии
        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $user->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Vos informations ont été mises à jour.');

            return $this->redirectToRoute('app_profile_edit');
        }

        // Форма пароля
        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $newPassword = (string) $passwordForm->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $user->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Mot de passe mis à jour.');

            return $this->redirectToRoute('app_profile_edit');
        }

        return $this->render('profile/edit.html.twig', [
            'profileForm' => $profileForm->createView(),
            'passwordForm' => $passwordForm->createView(),
        ]);
    }

}
