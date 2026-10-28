<?php

namespace App\Command;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Category;
use App\Repository\CategoryRepository;

#[AsCommand(
    name: 'app:import-products',
    description: 'Import products from a JSON file into database',
)]
final class ImportProductsCommand extends Command
{
    // Mêmes règles que les migrations Version20261028090000 (catégories) et Version20261028120000 (consommables) :
    // sur une base neuve, les migrations passent avant l'import et ne trouvent encore aucun produit.
    private const ACCESSORY_TITLE_PREFIXES = ['Bouée', 'Fauteuil', 'Canapé', 'Gilet', 'Échelle'];
    private const MATTRESS_TITLE_PREFIX = 'Matelas gonflable double';
    private const REPLENISHABLE_REFERENCES = ['10', '16', '22', '28', '34', '39'];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CategoryRepository $categoryRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::OPTIONAL, 'Path to JSON file', 'var/data/catalogue.json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = (string) $input->getArgument('file');

        if (!is_file($file)) {
            $output->writeln("<error>File not found: {$file}</error>");
            return Command::FAILURE;
        }

        $data = json_decode((string) file_get_contents($file), true);

        if (!isset($data['catalogue']) || !is_array($data['catalogue'])) {
            $output->writeln('<error>Invalid JSON format</error>');
            return Command::FAILURE;
        }

        $repo = $this->em->getRepository(Product::class);

        $created = 0;

        $categoryCache = [];

        foreach ($data['catalogue'] as $row) {
            if (empty($row['reference'])) {
                continue;
            }


            $product = $repo->findOneBy(['reference' => $row['reference']]);

            if (!$product) {
                $product = new Product();
                $product->setReference($row['reference']);
                // Produit existant : on garde le choix fait dans l'admin
                $product->setReplenishable(in_array((string) $row['reference'], self::REPLENISHABLE_REFERENCES, true));
                $this->em->persist($product);
                $created++;
            }


            $label = trim((string) ($row['category'] ?? ''));
            $label = mb_strtolower($label);

            if ($label === '') {

                $output->writeln('<error>Category is missing for reference: '.$row['reference'].'</error>');
                continue;
            }

            $label = $this->resolveCategoryLabel($label, (string) ($row['title'] ?? ''));

            if (!isset($categoryCache[$label])) {
                $category = $this->categoryRepository->findOneBy(['label' => $label]);

                if (!$category) {
                    $category = new Category();
                    $category->setLabel($label);
                    $this->em->persist($category);
                }

                $categoryCache[$label] = $category;
            }

            $product->setCategory($categoryCache[$label]);

            $product->setTitle($row['title'] ?? '');
            $product->setDescription($row['description'] ?? null);
            $product->setVolumeM3((float) ($row['volume_m3'] ?? 0));
            $product->setWeightKg((float) ($row['weight_kg'] ?? 0));
            $product->setPriceCents((int) round(((float) ($row['price_eur'] ?? 0)) * 100));
            $product->setImage($row['image'] ?? null);
            $product->setStock((int) ($row['stock'] ?? 50));
            }

        $this->em->flush();

        $output->writeln("Imported products: {$created}");

        return Command::SUCCESS;
    }

    // Le JSON ne connaît pas "accessoires" : on y range bouées, fauteuils, canapés, gilets et échelles
    private function resolveCategoryLabel(string $label, string $title): string
    {
        if (str_starts_with($title, self::MATTRESS_TITLE_PREFIX)) {
            return 'matelas';
        }

        if ($label === 'entretien') {
            foreach (self::ACCESSORY_TITLE_PREFIXES as $prefix) {
                if (str_starts_with($title, $prefix)) {
                    return 'accessoires';
                }
            }
        }

        return $label;
    }
}
