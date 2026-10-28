<?php

namespace App\Repository;

use App\Entity\CarePlan;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CarePlan> */
class CarePlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, CarePlan::class); }

    /** @return CarePlan[] */
    public function forUser(User $user): array
    {
        return $this->createQueryBuilder('plan')->addSelect('product')
            ->join('plan.product', 'product')->andWhere('plan.user = :user')->setParameter('user', $user)
            ->orderBy('plan.createdAt', 'DESC')->getQuery()->getResult();
    }
}
