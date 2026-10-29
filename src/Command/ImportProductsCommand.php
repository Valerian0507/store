<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Imports or updates the catalogue (matched by reference) from data/catalogue.json.
 * Product photos are copied from data/products/ to public/uploads/products/ when missing,
 * so a fresh install (or a server) gets the images without committing public/uploads/.
 */
#[AsCommand(
    name: 'app:import-products',
    description: 'Import products from a JSON file into database',
)]
final class ImportProductsCommand extends Command
{
    private const DEFAULT_STOCK = 50;
    private const REPLENISHABLE_REFERENCES = ['10', '16', '22', '28', '34', '39'];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CategoryRepository $categoryRepository,
        private readonly Filesystem $filesystem,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::OPTIONAL, 'Path to JSON file', 'data/catalogue.json');
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
        $updated = 0;
        $copiedImages = 0;
        $categoryCache = [];

        foreach ($data['catalogue'] as $row) {
            if (empty($row['reference'])) {
                continue;
            }

            $label = mb_strtolower(trim((string) ($row['category'] ?? '')));

            if ($label === '') {
                $output->writeln('<error>Category is missing for reference: '.$row['reference'].'</error>');
                continue;
            }

            $product = $repo->findOneBy(['reference' => $row['reference']]);

            if (!$product) {
                $product = new Product();
                $product->setReference($row['reference']);
                // Existing product: stock, "replenishable" and an uploaded photo stay as managed in the admin
                $product->setStock((int) ($row['stock'] ?? self::DEFAULT_STOCK));
                $product->setReplenishable(in_array((string) $row['reference'], self::REPLENISHABLE_REFERENCES, true));
                $this->em->persist($product);
                $created++;
            } else {
                $updated++;
            }

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
            // A photo uploaded from the admin wins over the catalogue photo
            if ($product->getImage() === null || $product->getImage() === '') {
                $product->setImage($row['image'] ?? null);
            }

            if ($product->getImage() === ($row['image'] ?? null) && !empty($row['image']) && $this->copyImageIfMissing((string) $row['image'])) {
                $copiedImages++;
            }
        }

        $this->em->flush();

        $output->writeln("Imported products: {$created}, updated: {$updated}, images copied: {$copiedImages}");

        return Command::SUCCESS;
    }

    // An image already in public/uploads (e.g. replaced from the admin) is never overwritten
    private function copyImageIfMissing(string $image): bool
    {
        $source = $this->projectDir.'/data/products/'.basename($image);
        $target = $this->projectDir.'/public/uploads/products/'.basename($image);

        if (!is_file($source) || is_file($target)) {
            return false;
        }

        $this->filesystem->copy($source, $target);

        return true;
    }
}
