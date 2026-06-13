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

    /**
     * For the catalog: a list of products with predictable sorting.
     *
     * @return Product[] Returns an array of Product objects
     */

    // Méthodes privées — chaque responsabilité est isolée
    private function createCatalogQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('products');
    }

    private function applyCategoryFilter(QueryBuilder $qb, ?int $categoryId): void
    {
         if ($categoryId !== null) {
            $qb->andWhere('products.category = :category')
                ->setParameter('category', $categoryId);
        }
    }

    private function applySearch(QueryBuilder $qb, ?string $q): void
    {
        if ($q !== null && $q !== '') {
            $qb
                ->andWhere('LOWER(products.title) LIKE LOWER(:q)')
                ->setParameter('q', '%' . $q . '%');
        }
    }

    private function applySort(QueryBuilder $qb, string $sort): void
    {
        match ($sort) {
            'price_asc'  => $qb->orderBy('products.priceCents', 'ASC')
                               ->addOrderBy('products.id', 'DESC'),
            'price_desc' => $qb->orderBy('products.priceCents', 'DESC')
                               ->addOrderBy('products.id', 'DESC'),
            'title_asc'  => $qb->orderBy('products.title', 'ASC')
                               ->addOrderBy('products.id', 'DESC'),
            'title_desc' => $qb->orderBy('products.title', 'DESC')
                               ->addOrderBy('products.id', 'DESC'),
            default      => $qb->orderBy('products.id', 'DESC'),
        };
    }

    // Méthode publique — composition claire des méthodes privées
    public function findForCatalog(
        int $page = 1,
        int $perPage = 20,
        ?int $category = null,
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


    public function findOneForCatalog(int $id): ?Product
    {
        return $this->createQueryBuilder('products')
            ->andWhere('products.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return int
     * A request that returns the total number of items.
     */
    public function countForCatalog(?int $categoryId = null, ?string $q = null): int
    {
        $qb = $this->createCatalogQueryBuilder()
            ->select('COUNT(products.id)');

        $this->applyCategoryFilter($qb, $categoryId);
        $this->applySearch($qb, $q);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
