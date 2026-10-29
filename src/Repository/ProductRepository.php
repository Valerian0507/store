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

    // Private methods - each one has a single responsibility
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
        // Search in the title and the reference
        if ($q !== null && $q !== '') {
            $qb
                ->andWhere('(LOWER(products.title) LIKE LOWER(:q) OR LOWER(products.reference) LIKE LOWER(:q))')
                ->setParameter('q', '%' . $q . '%');
        }
    }

    private function applyPriceFilter(QueryBuilder $qb): void
    {
        $qb->andWhere('products.priceCents > 0');
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

    /** @return Product[] */
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

        // Category loaded with the product: shown on each card without an extra query
        $qb = $this->createCatalogQueryBuilder()
            ->leftJoin('products.category', 'category')
            ->addSelect('category')
            ->setFirstResult($offset)
            ->setMaxResults($perPage);

        $this->applyCategoryFilter($qb, $category);
        $this->applyPriceFilter($qb);
        $this->applySearch($qb, $q);
        $this->applySort($qb, $sort);

        return $qb->getQuery()->getResult();
    }


    /**
     * @return Product[]
     * Home page selection: the latest product of each category (with image, price and stock).
     */
    public function findHomeSelection(int $limit = 4): array
    {
        $latestPerCategory = $this->createQueryBuilder('p2')
            ->select('MAX(p2.id)')
            ->andWhere('p2.priceCents > 0')
            ->andWhere('p2.stock > 0')
            ->andWhere('p2.image IS NOT NULL')
            ->groupBy('p2.category');

        return $this->createCatalogQueryBuilder()
            ->andWhere('products.id IN (' . $latestPerCategory->getDQL() . ')')
            ->orderBy('products.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Admin list: all products (even without a price), title/reference search, category filter, pagination.
     *
     * @return array{items: Product[], total: int}
     */
    public function findForAdmin(int $page, int $perPage, ?int $categoryId, ?string $q): array
    {
        $qb = $this->createCatalogQueryBuilder()
            ->leftJoin('products.category', 'category')
            ->addSelect('category');
        $this->applyCategoryFilter($qb, $categoryId);
        $this->applySearch($qb, $q);

        $total = (int) (clone $qb)->select('COUNT(products.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();

        $items = $qb->orderBy('products.id', 'DESC')
            ->setFirstResult((max(1, $page) - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => $total];
    }

    // Product page "Complétez votre installation": complementary categories, otherwise the same category
    private const COMPLEMENTS = [
        'piscines' => ['entretien', 'accessoires'],
        'spa' => ['entretien', 'accessoires'],
    ];

    /**
     * @return Product[]
     * Available products (price, stock, image), split evenly between the complementary categories
     * (e.g. pool: 2 care + 2 accessories), consumables first (chlorine, algicide…).
     */
    public function findComplements(Product $product, int $limit = 4): array
    {
        $label = mb_strtolower((string) $product->getCategory()?->getLabel());
        $labels = self::COMPLEMENTS[$label] ?? [$label];
        $perLabel = (int) ceil($limit / count($labels));

        $suggestions = [];
        foreach ($labels as $complementLabel) {
            $suggestions = [...$suggestions, ...$this->createCatalogQueryBuilder()
                ->innerJoin('products.category', 'category')
                ->addSelect('category')
                ->andWhere('LOWER(category.label) = :label')
                ->andWhere('products.id != :id')
                ->andWhere('products.priceCents > 0')
                ->andWhere('products.stock > 0')
                ->andWhere('products.image IS NOT NULL')
                ->setParameter('label', $complementLabel)
                ->setParameter('id', $product->getId())
                ->orderBy('products.replenishable', 'DESC')
                ->addOrderBy('products.id', 'DESC')
                ->setMaxResults($perLabel)
                ->getQuery()
                ->getResult()];
        }

        return array_slice($suggestions, 0, $limit);
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
        $this->applyPriceFilter($qb);
        $this->applySearch($qb, $q);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
