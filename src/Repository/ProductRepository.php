<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;


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
//     *
//     * @return Product[] Returns an array of Product objects
//     */

    private function createCatalogQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('product');
    }

    private function applyCategoryFilter(QueryBuilder $qb, ?string $category): void
    {
        if ($category !== null && $category !== '') {
            $qb->andWhere('product.category = :category')
                ->setParameter('category', $category);
        }
    }

    private function applySearch(QueryBuilder $qb, ?string $q): void
    {
        if ($q !== null && $q !== '') {
            $qb
                ->andWhere('LOWER(product.title) LIKE LOWER(:q)')
                ->setParameter('q', '%' . $q . '%');
        }
    }



    private function applySort(QueryBuilder $qb, string $sort): void
    {
        match ($sort) {
            'price_asc'  => $qb->orderBy('product.priceCents', 'ASC')->addOrderBy('product.id', 'DESC'),
            'price_desc' => $qb->orderBy('product.priceCents', 'DESC')->addOrderBy('product.id', 'DESC'),
            'title_asc'  => $qb->orderBy('product.title', 'ASC')->addOrderBy('product.id', 'DESC'),
            'title_desc' => $qb->orderBy('product.title', 'DESC')->addOrderBy('product.id', 'DESC'),
            default      => $qb->orderBy('product.id', 'DESC'),
        };
    }


    public function findForCatalog(
        int $page = 1,
        int $perPage = 20,
        ?string $category = null,
        string $sort = 'newest',
        ?string $q = null
    ): array {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $qb = $this->createCatalogQueryBuilder()
            ->setFirstResult($offset)
            ->setMaxResults($perPage);

        $this->applyCategoryFilter($qb, $category);
        $this->applySearch($qb, $q);
        $this->applySort($qb, $sort);

        return $qb->getQuery()->getResult();
    }


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

    /**
     * @return int
     * Запрос, который возвращает общее количество товаров.
     */
    public function countForCatalog(?string $category = null, ?string $q = null): int
    {
        $qb = $this->createCatalogQueryBuilder()
            ->select('COUNT(product.id)');

        $this->applyCategoryFilter($qb, $category);
        $this->applySearch($qb, $q);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }


//     1) Список категорий (для select)
    public function findAllCategories(): array
    {
        $rows = $this->createQueryBuilder('product')
            ->select('DISTINCT product.category AS category')
            ->where('product.category IS NOT NULL')
            ->andWhere('product.category <> :empty')
            ->setParameter('empty', '')
            ->orderBy('product.category', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn(array $r) => $r['category'], $rows);
    }

    // 2) Список товаров для каталога с опциональным фильтром

}
