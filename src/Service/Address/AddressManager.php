<?php

namespace App\Service\Address;


use App\Entity\Address;
use App\Entity\User;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final class AddressManager
{

    public function __construct(
        private readonly AddressRepository $addressRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

     public function setDefaultForUser(User $user, Address $selectedAddress): void
    {
        $userAddresses = $this->addressRepository->findUserAddressesForProfile($user);
        $now = new \DateTimeImmutable();

        foreach ($userAddresses as $userAddress) {
            $shouldBeDefault = $userAddress->getId() === $selectedAddress->getId();

            if ($userAddress->isDefault() !== $shouldBeDefault) {
                $userAddress->setIsDefault($shouldBeDefault);
                $userAddress->setUpdatedAt($now);
            }
        }

        $this->entityManager->flush();
    }

    public function assignNewDefaultAfterDeletion(User $user): void
    {
        $remainingAddresses = $this->addressRepository->findUserAddressesForProfile($user);

        if ($remainingAddresses === []) {
            return;
        }

        $now = new \DateTimeImmutable();
        $newDefaultAddress = $remainingAddresses[0];

        foreach ($remainingAddresses as $address) {
            $shouldBeDefault = $address->getId() === $newDefaultAddress->getId();

            if ($address->isDefault() !== $shouldBeDefault) {
                $address->setIsDefault($shouldBeDefault);
                $address->setUpdatedAt($now);
            }
        }

        $this->entityManager->flush();
    }

    public function createForUser(User $user, Address $address): void
    {
        $address->setUser($user);
        $address->setCreatedAt(new \DateTimeImmutable());

        $hasAddress = $this->addressRepository->findOneBy(['user' => $user]) !== null;

        if (!$hasAddress) {
            $address->setIsDefault(true);
        }

        $this->entityManager->persist($address);
        $this->entityManager->flush();
    }
}
