<?php

// Local HTTP smoke check. All fixture writes are rolled back, never committed.
$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
(new Symfony\Component\Dotenv\Dotenv())->bootEnv($root . '/.env');
if (($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev') !== 'dev') throw new RuntimeException('Run this local smoke check only in the dev environment.');
ob_start();
$kernel = new App\Kernel('dev', false);
$kernel->boot();
$client = new Symfony\Bundle\FrameworkBundle\KernelBrowser($kernel);
$client->disableReboot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
$connection = $em->getConnection();
$connection->beginTransaction();
$messages = [];
$check = function (bool $condition, string $message) use (&$messages): void {
    if (!$condition) throw new RuntimeException($message);
    $messages[] = 'OK: ' . $message;
};
$status = fn (): int => $client->getResponse()->getStatusCode();
try {
    foreach (['/outils/calculateur-volume', '/outils/analyse-eau', '/'] as $url) {
        $client->request('GET', $url);
        $check($status() === 200, 'public page ' . $url . ' returns 200');
    }
    $client->request('GET', '/profile/abonnements');
    $check($client->getResponse()->isRedirection() && parse_url($client->getResponse()->headers->get('Location'), PHP_URL_PATH) === '/login', 'anonymous profile access redirects to login');
    $suffix = bin2hex(random_bytes(8));
    $password = bin2hex(random_bytes(16));
    $owner = (new App\Entity\User())->setEmail('tools-owner-' . $suffix . '@example.test')->setPassword(password_hash($password, PASSWORD_BCRYPT))->setIsVerified(true);
    $other = (new App\Entity\User())->setEmail('tools-other-' . $suffix . '@example.test')->setPassword(password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT))->setIsVerified(true);
    $em->persist($owner);
    $em->persist($other);
    $product = $em->getRepository(App\Entity\Product::class)->findOneBy(['replenishable' => true]);
    if ($product === null || $product->getStock() < 1) throw new RuntimeException('No stocked consumable available for the smoke check');
    $foreignPlan = new App\Entity\CarePlan($other, $product, 1, 4);
    $em->persist($foreignPlan);
    $em->flush();
    $login = $client->request('GET', '/login');
    $client->request('POST', '/login', ['email' => $owner->getEmail(), 'password' => $password, '_csrf_token' => $login->filter('input[name="_csrf_token"]')->attr('value')]);
    $check($client->getResponse()->isRedirection(), 'fixture user signs in through the normal login form');
    $crawler = $client->request('GET', '/products/' . $product->getId());
    $check($status() === 200 && $crawler->filter('.care-plan-form form')->count() === 1, 'eligible product renders planning form');
    $token = $crawler->filter('.care-plan-form input[name="_token"]')->attr('value');
    $createUrl = '/products/' . $product->getId() . '/planning';
    $client->request('POST', $createUrl, ['quantity' => 1, 'interval' => 4, '_token' => 'invalid']);
    $check($status() === 403, 'creating a plan rejects invalid CSRF');
    $client->request('POST', $createUrl, ['quantity' => 0, 'interval' => 4, '_token' => $token]);
    $check($status() === 303 && $em->getRepository(App\Entity\CarePlan::class)->count(['user' => $owner]) === 0, 'invalid quantity creates no plan');
    $client->request('POST', $createUrl, ['quantity' => 2, 'interval' => 6, '_token' => $token]);
    $check($status() === 303 && $client->getResponse()->isRedirect('/profile/abonnements'), 'valid plan is saved');
    $plan = $em->getRepository(App\Entity\CarePlan::class)->findOneBy(['user' => $owner]);
    $check($plan !== null && $plan->getQuantity() === 2, 'saved quantity is correct');
    $planId = $plan->getId();
    $client->request('POST', $createUrl, ['quantity' => 3, 'interval' => 8, '_token' => $token]);
    $plan = $em->find(App\Entity\CarePlan::class, $planId);
    $check($em->getRepository(App\Entity\CarePlan::class)->count(['user' => $owner]) === 1 && $plan->getQuantity() === 3, 'repeat submission updates rather than duplicates (HTTP ' . $status() . ', quantity ' . $plan->getQuantity() . ')');
    $client->request('POST', '/profile/abonnements/' . $foreignPlan->getId() . '/cancel', ['_token' => 'invalid']);
    $check($status() === 404 && $foreignPlan->getStatus() === 'active', 'another owner plan is inaccessible');
    $client->request('POST', '/profile/abonnements/' . $plan->getId() . '/cancel', ['_token' => 'invalid']);
    $check($status() === 403 && $plan->getStatus() === 'active', 'modification rejects invalid CSRF');
    foreach (['pause' => 'paused', 'resume' => 'active', 'cancel' => 'cancelled'] as $operation => $expected) {
        $crawler = $client->request('GET', '/profile/abonnements');
        $action = '/profile/abonnements/' . $plan->getId() . '/' . $operation;
        $form = $crawler->filter('form[action="' . $action . '"]');
        $client->request('POST', $action, ['_token' => $form->filter('input[name="_token"]')->attr('value')]);
        $plan = $em->find(App\Entity\CarePlan::class, $planId);
        $check($status() === 303 && $plan->getStatus() === $expected, 'plan operation ' . $operation . ' persists');
    }
    $check($em->getRepository(App\Entity\Order::class)->count(['user' => $owner]) === 0, 'demo does not create an order');
    $check($connection->isTransactionActive(), 'fixture transaction remains open for rollback');
} finally {
    if ($connection->isTransactionActive()) $connection->rollBack();
    $em->clear();
    $kernel->shutdown();
    ob_end_clean();
}
echo implode(PHP_EOL, $messages) . PHP_EOL;
