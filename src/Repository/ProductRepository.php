<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

//    /**
//     * Для каталога: список товаров с предсказуемой сортировкой.
//     * Позже сюда легко добавить пагинацию и фильтры.
//     *
//     * @return Product[] Returns an array of Product objects
//     */

    public function findForCatalog(int $page = 1, int $perPage= 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        return $this->createQueryBuilder('product')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
            ->orderBy('product.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult()
        ;
    }

//    public function findOneBySomeField($value): ?Product
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


    /**
     * Для страницы товара.
     */
    public function findOneForCatalog(int $id): ?Product
    {
        return $this->createQueryBuilder('product')
            ->andWhere('product.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
