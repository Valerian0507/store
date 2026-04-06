<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findAllUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC')
            ->addOrderBy('u.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUsersForAdminList(
        ?string $search = null,
        ?bool $verified = null,
        int $limit = 2,
        int $offset = 0
    ): array {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC')
            ->addOrderBy('u.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if ($search !== null) {
            $qb
                ->andWhere('u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($verified !== null) {
            $qb
                ->andWhere('u.isVerified = :verified')
                ->setParameter('verified', $verified);
        }

        return $qb->getQuery()->getResult();
    }

    public function countForAdminList(?string $search = null, ?bool $verified = null): int
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)');

        if ($search !== null) {
            $qb
                ->andWhere('u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($verified !== null) {
            $qb
                ->andWhere('u.isVerified = :verified')
                ->setParameter('verified', $verified);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findOneForAdminShow(int $id): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}


// Если совсем просто, то разница такая
// findAllUsers()

// Дай всех пользователей

// findUsersForAdminList(...)

// Дай пользователей для админского списка с фильтрами и пагинацией

// countForAdminList(...)

// Скажи, сколько всего пользователей найдено для тех же фильтров

// findOneForAdminShow($id)

// Дай одного пользователя для detail/show страницы

// upgradePassword(...)

// Служебный security-метод Symfony
