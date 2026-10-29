<?php

namespace App\Tests\Controller;

use App\Tests\Support\DatabaseTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

/**
 * Legal pages (table of contents = sections) and production error page.
 * 5xx error: the header and the footer do not query the database (categories replaced by "Catalogue").
 */
class PublicPagesTest extends WebTestCase
{
    use DatabaseTestTrait;

    #[DataProvider('legalPages')]
    public function testLegalPageListsItsSectionsInTheSummary(string $url, string $title, int $sections): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', $url);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', $title);
        self::assertCount($sections, $crawler->filter('.legal-toc__link'));
        self::assertCount($sections, $crawler->filter('.legal-section'));

        // Each table of contents link points to a section of the page
        foreach ($crawler->filter('.legal-toc__link')->each(fn (Crawler $link) => (string) $link->attr('href')) as $href) {
            self::assertCount(1, $crawler->filter('section' . $href));
        }
    }

    /** @return iterable<string, array{string, string, int}> */
    public static function legalPages(): iterable
    {
        yield 'mentions légales' => ['/mentions_legales', 'Mentions légales', 4];
        yield 'CGV' => ['/cgv', 'Conditions générales de vente', 6];
        yield 'confidentialité' => ['/politique_confidentialite', 'Politique de confidentialité', 7];
    }

    public function testNotFoundPageShowsCategoriesAndCatalogueLink(): void
    {
        $page = $this->renderErrorPage(404);

        self::assertStringContainsString('Cette page est introuvable', $page->filter('h1')->text());
        self::assertStringContainsString('Piscines', $page->filter('.site-header__nav')->text());
        self::assertCount(1, $page->filter('a[href="/products"]:contains("Découvrir le catalogue")'));
    }

    public function testServerErrorPageDoesNotQueryCategories(): void
    {
        $page = $this->renderErrorPage(500);

        self::assertStringContainsString('Un petit contretemps', $page->filter('h1')->text());
        self::assertSame('Catalogue', trim($page->filter('.site-header__nav')->text()));
        self::assertStringNotContainsString('Piscines', $page->filter('.site-footer')->text());
        // "Réessayer" reloads the requested page
        self::assertSame('/page-en-erreur', $page->filter('a:contains("Réessayer")')->attr('href'));
    }

    // Direct rendering of the error template (in test, the kernel shows the Symfony debug page)
    private function renderErrorPage(int $statusCode): Crawler
    {
        static::bootKernel();
        $this->resetDatabase();
        $this->createProduct('T-300', 'Piscine de test', category: 'piscines');

        /** @var RequestStack $requestStack */
        $requestStack = static::getContainer()->get(RequestStack::class);
        $requestStack->push(Request::create('/page-en-erreur'));

        /** @var Environment $twig */
        $twig = static::getContainer()->get(Environment::class);
        $html = $twig->render('bundles/TwigBundle/Exception/error.html.twig', [
            'status_code' => $statusCode,
            'status_text' => 'Erreur',
        ]);

        return new Crawler($html);
    }
}
