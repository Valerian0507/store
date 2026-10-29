<?php

namespace App\Twig;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides the categories to the main menu and the footer (present on every page).
 */
class NavigationExtension extends AbstractExtension
{
    // Desired display order; unknown categories go last
    private const ORDER = ['piscines', 'spa', 'accessoires', 'entretien', 'matelas'];

    /** @var Category[]|null */
    private ?array $categories = null;

    public function __construct(private CategoryRepository $categoryRepository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('nav_categories', [$this, 'getNavCategories']),
        ];
    }

    /** @return Category[] */
    public function getNavCategories(): array
    {
        // A single query per page, even if both the menu and the footer call it
        if ($this->categories === null) {
            $categories = $this->categoryRepository->findAllOrdered();

            usort($categories, function (Category $a, Category $b): int {
                $posA = array_search(mb_strtolower($a->getLabel()), self::ORDER, true);
                $posB = array_search(mb_strtolower($b->getLabel()), self::ORDER, true);

                return ($posA === false ? PHP_INT_MAX : $posA) <=> ($posB === false ? PHP_INT_MAX : $posB);
            });

            $this->categories = $categories;
        }

        return $this->categories;
    }
}
