<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional access control tests.
 *
 * They really boot the application (HttpKernel) and check:
 *  - that the public pages respond (200);
 *  - that the protected areas (/admin, /profile, /checkout) redirect
 *    an anonymous visitor to the login page.
 *
 * Security redirects are decided by the firewall BEFORE
 * the controller runs: these tests therefore do not depend on the content
 * of the database.
 *
 * Requirements to run this file locally:
 *   1. create the test database: php bin/console --env=test doctrine:database:create
 *   2. create the schema: php bin/console --env=test doctrine:migrations:migrate -n
 *   3. run the suite: php bin/phpunit tests/Controller
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

    // 403 for a logged-in customer on /admin: tests/Controller/Admin/AdminListsTest.php
}
