<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels du contrôle d'accès.
 *
 * Ils démarrent réellement l'application (HttpKernel) et vérifient :
 *  - que les pages publiques répondent bien (200) ;
 *  - que les zones protégées (/admin, /profile, /checkout) renvoient
 *    un visiteur anonyme vers la page de connexion.
 *
 * Les redirections de sécurité sont décidées par le pare-feu AVANT
 * l'exécution du contrôleur : ces tests ne dépendent donc pas du contenu
 * de la base de données.
 *
 * Pré-requis pour lancer ce fichier en local :
 *   1. créer la base de test     : php bin/console --env=test doctrine:database:create
 *   2. créer le schéma            : php bin/console --env=test doctrine:migrations:migrate -n
 *   3. lancer la suite            : php bin/phpunit tests/Controller
 */
class SecurityAccessTest extends WebTestCase
{
    public function testHomePageIsPublic(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testLoginPageIsPublic(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
    }

    public function testAdminAreaRedirectsAnonymousToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        $this->assertResponseRedirects();
        $this->assertStringContainsString(
            '/login',
            (string) $client->getResponse()->headers->get('Location')
        );
    }

    public function testProfileAreaRedirectsAnonymousToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/profile');

        $this->assertResponseRedirects();
        $this->assertStringContainsString(
            '/login',
            (string) $client->getResponse()->headers->get('Location')
        );
    }

    public function testCheckoutRedirectsAnonymousToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/checkout');

        $this->assertResponseRedirects();
        $this->assertStringContainsString(
            '/login',
            (string) $client->getResponse()->headers->get('Location')
        );
    }

    /*
     * Exemple à activer une fois une fixture utilisateur disponible en test :
     * vérifie qu'un utilisateur authentifié SANS le rôle ROLE_ADMIN reçoit
     * bien un 403 sur la zone d'administration.
     *
     * public function testAdminAreaIsForbiddenForRegularUser(): void
     * {
     *     $client = static::createClient();
     *     $user = static::getContainer()
     *         ->get(\App\Repository\UserRepository::class)
     *         ->findOneBy(['email' => 'user@example.com']);
     *
     *     $client->loginUser($user);
     *     $client->request('GET', '/admin');
     *
     *     $this->assertResponseStatusCodeSame(403);
     * }
     */
}
