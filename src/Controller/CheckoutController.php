<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\User;
use App\Repository\AddressRepository;
use App\Repository\OrderRepository;
use App\Service\Checkout\CheckoutService;
use App\Service\Checkout\CheckoutSummaryBuilder;
use App\Service\Checkout\OrderSuccessBuilder;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/checkout', name: 'app_checkout_')]
#[IsGranted('ROLE_USER')]
final class CheckoutController extends AbstractController
{

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CheckoutSummaryBuilder $checkoutSummaryBuilder, AddressRepository $addressRepository): Response
    {
        $summary = $checkoutSummaryBuilder->build();

        if ($summary->isEmpty()) {
            $this->addFlash('warning', 'Votre panier est vide.');

            return $this->redirectToRoute('app_cart_index');
        }

        $user = $this->getUser();

        $addresses = [];
        $currentAddress = null;
        $selectedAddressId = 0;

        $shippingData = [
        'firstName' => $user instanceof User ? ($user->getFirstName() ?? '') : '',
        'lastName' => $user instanceof User ? ($user->getLastName() ?? '') : '',
        'street' => '',
        'city' => '',
        'postalCode' => '',
        'country' => '',
        ];

        if($user instanceof User) {
                $addresses = $addressRepository->findUserAddressesForProfile($user);
                $currentAddress = $addressRepository->findDefaultAddressForUser($user);

                if($currentAddress !== null) {
                    $selectedAddressId = $currentAddress->getId();

                $shippingData = [
                'firstName' => $user->getFirstName() ?? '',
                'lastName' => $user->getLastName() ?? '',
                'street' => $currentAddress->getStreet(),
                'city' => $currentAddress->getCity(),
                'postalCode' => $currentAddress->getPostalCode(),
                'country' => $currentAddress->getCountry(),
                ];
            }
        }


        return $this->render('checkout/index.html.twig', [
            'currentAddress' => $currentAddress,
            'addresses' => $addresses,
            'selectedAddressId' => $selectedAddressId,
            'summary' => $summary,
            'errors' => [],
            'shippingData' => $shippingData,
        ]);
    }

    // Order data collection and shipping, CSRF verification, user receipt, order creation, redirect
    #[Route('', name: 'submit', methods: ['POST'])]
    public function submit(Request $request, CheckoutService $checkoutService, CheckoutSummaryBuilder $checkoutSummaryBuilder, AddressRepository $addressRepository ): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException("Utilisateur n'est pas connecté.");
        }

        $token = (string) $request->request->get('_token', '');

        if (!$this->isCsrfTokenValid('checkout', $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $selectedAddressId = (int) $request->request->get('selectedAddress', 0);

        $addresses = $addressRepository->findUserAddressesForProfile($user);
        $currentAddress = null;

        if ($selectedAddressId > 0) {
            $address = $addressRepository->findOneByIdAndUser($selectedAddressId, $user);

            if ($address === null) {
                throw $this->createNotFoundException('Adresse introuvable.');
            }

            $currentAddress = $address;

            $shippingData = [
                'firstName' => $user->getFirstName() ?? '',
                'lastName' => $user->getLastName() ?? '',
                'street' => $address->getStreet() ?? '',
                'city' => $address->getCity() ?? '',
                'postalCode' => $address->getPostalCode() ?? '',
                'country' => $address->getCountry() ?? '',
            ];
        } else {
            $shippingData = [
                'firstName' => trim((string) $request->request->get('firstName', '')),
                'lastName' => trim((string) $request->request->get('lastName', '')),
                'street' => trim((string) $request->request->get('street', '')),
                'city' => trim((string) $request->request->get('city', '')),
                'postalCode' => trim((string) $request->request->get('postalCode', '')),
                'country' => trim((string) $request->request->get('country', '')),
            ];
        }

        $errors = $this->validateShippingData($shippingData);

        if ($errors !== []) {
            return $this->render('checkout/index.html.twig', [
                'addresses' => $addresses,
                'currentAddress' => $currentAddress,
                'selectedAddressId' => $selectedAddressId,
                'summary' => $checkoutSummaryBuilder->build(),
                'errors' => $errors,
                'shippingData' => $shippingData,
            ], new Response(status: 422));
        }

        try {
            $order = $checkoutService->createOrderFromCart($user, $shippingData);
        } catch (LogicException $e) {
            $this->addFlash('error', 'Votre panier est vide.');

            return $this->redirectToRoute('app_cart_index');
        }

        return $this->redirectToRoute('app_checkout_success', [
            'id' => $order->getId(),
        ]);
    }

    #[Route('/success/{id}', name: 'success', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function success(int $id, OrderSuccessBuilder $orderSuccessBuilder): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not authenticated.');
        }

        $order = $orderSuccessBuilder->buildForUser($id, $user);

        if ($order === null) {
            throw $this->createNotFoundException('Order not found.');
        }


        return $this->render('checkout/success.html.twig', [
            'order' => $order,
        ]);
    }
    /**
    * @param array<string, string> $shippingData
    *
    * @return array<string, string>
    */

    private function validateShippingData(array $shippingData): array
    {
        $errors = [];

        foreach ([
            'firstName',
            'lastName',
            'street',
            'city',
            'postalCode',
            'country',
        ] as $field) {
            if ($shippingData[$field] === '') {
                $errors[$field] = 'This field is required.';
            }
        }

        if ($shippingData['firstName'] !== '' && mb_strlen($shippingData['firstName']) > 100) {
            $errors['firstName'] = 'First name is too long.';
        }

        if ($shippingData['lastName'] !== '' && mb_strlen($shippingData['lastName']) > 100) {
            $errors['lastName'] = 'Last name is too long.';
        }

        if ($shippingData['street'] !== '' && mb_strlen($shippingData['street']) > 255) {
            $errors['street'] = 'Street is too long.';
        }

        if ($shippingData['city'] !== '' && mb_strlen($shippingData['city']) > 120) {
            $errors['city'] = 'City is too long.';
        }

        if ($shippingData['postalCode'] !== '' && mb_strlen($shippingData['postalCode']) > 20) {
            $errors['postalCode'] = 'Postal code is too long.';
        }

        if ($shippingData['country'] !== '' && mb_strlen($shippingData['country']) > 120) {
            $errors['country'] = 'Country is too long.';
        }

        return $errors;
    }



}


