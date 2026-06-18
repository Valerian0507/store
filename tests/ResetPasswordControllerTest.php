<?php

namespace App\Tests;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetPasswordControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Ensure we have a clean database
        $container = static::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();
        $this->em = $em;

        $this->userRepository = $container->get(UserRepository::class);

        // Nettoie les demandes de réinitialisation (clé étrangère vers user)
        $this->em->createQuery('DELETE FROM App\Entity\ResetPasswordRequest')->execute();

        foreach ($this->userRepository->findAll() as $user) {
            $this->em->remove($user);
        }

        $this->em->flush();
    }

    public function testResetPasswordController(): void
    {
        // Create a test user
        $user = (new User())
            ->setEmail('me@example.com')
            ->setPassword('a-test-password-that-will-be-changed-later')
        ;
        $this->em->persist($user);
        $this->em->flush();

        // Test Request reset password page
        $this->client->request('GET', '/reset-password');

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Mot de passe oublié');

        // Submit the reset password form and test email message is queued / sent
        $this->client->submitForm('Envoyer le lien', [
            'reset_password_request_form[email]' => 'me@example.com',
        ]);

        // Ensure the reset password email was sent
        // Use either assertQueuedEmailCount() || assertEmailCount() depending on your mailer setup
        // self::assertQueuedEmailCount(1);
        self::assertEmailCount(1);

        $messages = $this->getMailerMessages();

        self::assertEmailAddressContains($messages[0], 'from', 'no-reply@store.com');
        self::assertEmailAddressContains($messages[0], 'to', 'me@example.com');
        self::assertEmailHtmlBodyContains($messages[0], 'Pour réinitialiser votre mot de passe');

        self::assertResponseRedirects('/reset-password/check-email');

        // Test check email landing page shows correct "expires at" time
        $crawler = $this->client->followRedirect();

        self::assertPageTitleContains('E-mail envoyé');
        self::assertStringContainsString('Ce lien expirera', $crawler->html());

        // Test the link sent in the email is valid
        $email = quoted_printable_decode($messages[0]->toString());
        preg_match('#(/reset-password/reset/[a-zA-Z0-9]+)#', $email, $resetLink);

        $this->client->request('GET', $resetLink[1]);

        self::assertResponseRedirects('/reset-password/reset');

        $this->client->followRedirect();

        // Test we can set a new password
        $this->client->submitForm('Réinitialiser', [
            'change_password_form[plainPassword][first]' => 'Zt7&qLm9!xVr2Wp',
            'change_password_form[plainPassword][second]' => 'Zt7&qLm9!xVr2Wp',
        ]);

        self::assertResponseRedirects('/login');

        $user = $this->userRepository->findOneBy(['email' => 'me@example.com']);

        self::assertInstanceOf(User::class, $user);

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertTrue($passwordHasher->isPasswordValid($user, 'Zt7&qLm9!xVr2Wp'));
    }
}
