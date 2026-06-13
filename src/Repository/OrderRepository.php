<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    // searches for all user's orders
    /** @return Order[] */
    public function findUserOrdersForProfile(User $user): array
    {
        return $this->createQueryBuilder('orders')
            ->andWhere('orders.user = :user')
            ->setParameter('user', $user)
            ->orderBy('orders.createdAt', 'DESC')
            ->addOrderBy('orders.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // searches for one order by a specific id
    public function findOneForSuccessPage(int $orderId, User $user): ?Order
    {
        return $this->createQueryBuilder('orders')
            ->leftJoin('orders.items', 'items')
            ->addSelect('items')
            ->andWhere('orders.id = :orderId')
            ->setParameter('orderId', $orderId)
            ->andWhere('orders.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    	/** @return Order[] */
     public function findOrdersForAdminList(?string $status = null, ?string $search = null, int $limit = 20, int $offset = 0 ): array
     {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')
            ->addSelect('u')
            ->orderBy('o.createdAt', 'DESC')
            ->addOrderBy('o.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if ($status !== null) {
            $qb
                ->andWhere('o.status = :status')
                ->setParameter('status', $status);
        }

        if ($search !== null) {
            $qb
                ->andWhere('o.reference LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

     public function countForAdminList(?string $status = null, ?string $search = null): int
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')
            ->select('COUNT(o.id)');

        if ($status !== null) {
            $qb
                ->andWhere('o.status = :status')
                ->setParameter('status', $status);
        }

        if ($search !== null) {
            $qb
                ->andWhere('o.reference LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }



    public function findOneForAdminShow(int $id): ?Order
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')
            ->addSelect('u')
            ->leftJoin('o.items', 'i')
            ->addSelect('i')
            ->where('o.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    	/** @return Order[] */
    public function findRecentOrdersForUserForAdminShow(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->addOrderBy('o.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
