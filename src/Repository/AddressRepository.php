<?php

namespace App\Repository;

use App\Entity\Address;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Address>
 */
class AddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }

    // ищет все адреса пользователя
    public function findUserAddressesForProfile(User $user): array
    {
        return $this->createQueryBuilder('address')
            ->andWhere('address.user = :user')
            ->setParameter('user', $user)
            ->orderBy('address.createdAt', 'DESC')
            ->addOrderBy('address.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // ищет один адрес по id
    public function findOneByIdAndUser(int $id, User $user): ?Address
    {
        return $this->createQueryBuilder('address')
            ->andWhere('address.id = :id')
            ->setParameter('id', $id)
            ->andWhere('address.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDefaultAddressForUser(User $user): ?Address
    {
        return $this->createQueryBuilder('address')
            ->andWhere('address.user = :user')
            ->setParameter('user', $user)
            ->andWhere('address.isDefault = :isDefault')
            ->setParameter('isDefault', true)
            ->orderBy('address.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        } 
}
