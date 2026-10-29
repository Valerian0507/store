<?php

namespace App\Tests\Controller;

use App\Entity\NewsletterSubscriber;
use App\Tests\Support\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Newsletter: sign-up from the home page (welcome email), address already subscribed,
 * unsubscribe through the email link (confirmed by a POST) and from the account page.
 */
class NewsletterControllerTest extends WebTestCase
{
    use DatabaseTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->resetDatabase();
    }

    public function testSubscribeFromHomeSendsWelcomeEmail(): void
    {
        $this->subscribe('lecteur@example.test');

        self::assertResponseRedirects('/#newsletter');
        self::assertEmailCount(1);

        $email = self::getMailerMessage();
        self::assertNotNull($email);
        self::assertEmailAddressContains($email, 'to', 'lecteur@example.test');
        self::assertEmailSubjectContains($email, 'Bienvenue dans la newsletter');
        self::assertEmailHtmlBodyContains($email, 'Se désinscrire');
        self::assertEmailHeaderSame($email, 'List-Unsubscribe', '<' . $this->unsubscribeUrl('lecteur@example.test') . '>');

        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Votre inscription à la newsletter est bien enregistrée');
    }

    public function testAlreadySubscribedAddressGetsSameMessageWithoutNewEmail(): void
    {
        $this->em()->persist(new NewsletterSubscriber('deja@example.test'));
        $this->em()->flush();

        $this->subscribe('deja@example.test');

        // Same message as a new sign-up: who is subscribed is not revealed
        self::assertEmailCount(0);
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Votre inscription à la newsletter est bien enregistrée');
    }

    public function testInvalidEmailIsRejected(): void
    {
        $this->subscribe('pas-une-adresse');

        self::assertEmailCount(0);
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', "Cette adresse e-mail n'est pas valide.");
        self::assertSame(0, $this->subscriberCount());
    }

    public function testUnsubscribeLinkAsksForConfirmationThenRemovesSubscriber(): void
    {
        $subscriber = new NewsletterSubscriber('partant@example.test');
        $this->em()->persist($subscriber);
        $this->em()->flush();

        // A plain GET (link preview by the email client) does not unsubscribe
        $crawler = $this->client->request('GET', '/newsletter/desinscription/' . $subscriber->getUnsubscribeToken());
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->subscriberCount());

        $this->client->submit($crawler->filter('form[method="post"]')->form());

        self::assertResponseRedirects('/');
        self::assertSame(0, $this->subscriberCount());
    }

    public function testUnknownUnsubscribeTokenReturns404(): void
    {
        $this->client->request('GET', '/newsletter/desinscription/' . str_repeat('a', 64));

        self::assertResponseStatusCodeSame(404);
    }

    public function testAccountSwitchSubscribesAndUnsubscribes(): void
    {
        $user = $this->createUser('client@example.test');
        $this->client->loginUser($user);

        // Switch checked then unchecked ("newsletter" checkbox)
        $crawler = $this->client->request('GET', '/profile');
        $this->client->submit($crawler->filter('#newsletter form')->form(['newsletter' => true]));

        self::assertResponseRedirects('/profile#newsletter');
        self::assertSame(1, $this->subscriberCount());
        self::assertEmailCount(1);

        $crawler = $this->client->request('GET', '/profile');
        $this->client->submit($crawler->filter('#newsletter form')->form(['newsletter' => false]));

        self::assertSame(0, $this->subscriberCount());
    }

    private function subscribe(string $email): void
    {
        $crawler = $this->client->request('GET', '/');
        $form = $crawler->filter('form.home-newsletter__form')->form(['newsletter[email]' => $email]);
        $this->client->submit($form);
    }

    private function subscriberCount(): int
    {
        return (int) $this->em()->getConnection()->fetchOne('SELECT COUNT(*) FROM newsletter_subscribers');
    }

    private function unsubscribeUrl(string $email): string
    {
        $token = $this->em()->getConnection()->fetchOne('SELECT unsubscribe_token FROM newsletter_subscribers WHERE email = ?', [$email]);

        return 'http://localhost/newsletter/desinscription/' . $token;
    }
}
