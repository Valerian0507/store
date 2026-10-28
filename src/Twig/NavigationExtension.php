<?php

namespace App\Twig;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Fournit les catégories au menu principal et au footer (présents sur toutes les pages).
 */
class NavigationExtension extends AbstractExtension
{
    // Ordre d'affichage souhaité ; les catégories inconnues passent à la fin
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
        // Une seule requête par page, même si le menu et le footer l'appellent
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
