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
    private const FILTER_BY_USER = 'address.user = :user';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }


    // searches for all user addresses
    /** @return Address[] */
    public function findUserAddressesForProfile(User $user): array
    {
        return $this->createQueryBuilder('address')
            ->andWhere(self::FILTER_BY_USER)
            ->setParameter('user', $user)
            ->orderBy('address.createdAt', 'DESC')
            ->addOrderBy('address.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // searches for one address by id
    public function findOneByIdAndUser(int $id, User $user): ?Address
    {
        return $this->createQueryBuilder('address')
            ->andWhere('address.id = :id')
            ->setParameter('id', $id)
            ->andWhere(self::FILTER_BY_USER)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDefaultAddressForUser(User $user): ?Address
    {
        return $this->createQueryBuilder('address')
            ->andWhere(self::FILTER_BY_USER)
            ->setParameter('user', $user)
            ->andWhere('address.isDefault = :isDefault')
            ->setParameter('isDefault', true)
            ->orderBy('address.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
