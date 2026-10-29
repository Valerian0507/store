<?php

namespace App\Tests\Support;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Test data for the functional tests (WebTestCase).
 *
 * The store_db_test database is only used by the tests: resetDatabase() empties it
 * before each test, so that every test starts from a known state.
 */
trait DatabaseTestTrait
{
    public const TEST_PASSWORD = 'Motdepasse1!';

    protected function resetDatabase(): void
    {
        /** @var Connection $connection */
        $connection = static::getContainer()->get(Connection::class);

        $tables = $connection->fetchFirstColumn(
            "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name <> 'doctrine_migration_versions'"
        );

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $table) {
            $connection->executeStatement(sprintf('DELETE FROM `%s`', $table));
        }
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    protected function em(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        return $em;
    }

    /** @param list<string> $roles */
    protected function createUser(string $email, array $roles = [], string $firstName = 'Camille'): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName('Martin')
            ->setRoles($roles)
            ->setIsVerified(true);

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, self::TEST_PASSWORD));

        $this->em()->persist($user);
        $this->em()->flush();

        return $user;
    }

    protected function createProduct(string $reference, string $title, int $stock = 5, int $priceCents = 1990, string $category = 'piscines'): Product
    {
        $categoryEntity = $this->em()->getRepository(Category::class)->findOneBy(['label' => $category]);

        if ($categoryEntity === null) {
            $categoryEntity = (new Category())->setLabel($category);
            $this->em()->persist($categoryEntity);
        }

        $product = (new Product())
            ->setReference($reference)
            ->setTitle($title)
            ->setCategory($categoryEntity)
            ->setPriceCents($priceCents)
            ->setStock($stock);

        $this->em()->persist($product);
        $this->em()->flush();

        return $product;
    }
}
