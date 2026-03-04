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

            if ($repo->findOneBy(['reference' => $row['reference']])) {
                continue; // уже есть
            }

            // $product = new Product();
            // $product->setReference($row['reference']);
            // $product->setCategory($row['category'] ?? '');
            $product = new Product();
            $product->setReference($row['reference']);

            // ---- ВОТ СЮДА ВСТАВЛЯЕМ ----
            $label = trim((string) ($row['category'] ?? ''));
            $label = mb_strtolower($label);

            if ($label === '') {
                // у тебя JoinColumn(nullable=false), значит category НЕ может быть null
                $output->writeln('<error>Category is missing for reference: '.$row['reference'].'</error>');
                continue;
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
            // ---- ДО СЮДА ----
            $product->setTitle($row['title'] ?? '');
           
            $product->setDescription($row['description'] ?? null);
            $product->setVolumeM3((float) ($row['volume_m3'] ?? 0));
            // $product->setWeightKg((float) ($row['weight_kg'] ?? 0));
            $product->setPriceCents((int) round(((float) ($row['price_eur'] ?? 0)) * 100));
            $product->setImage($row['image'] ?? null);
            

            $this->em->persist($product);
            $created++;
        }

        $this->em->flush();

        $output->writeln("Imported products: {$created}");

        return Command::SUCCESS;
    }
}
