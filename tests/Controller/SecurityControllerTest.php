<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SecurityControllerTest extends WebTestCase
{
    public function testLoginPageIsPubliclyAccessible(): void
    {
        $client = static::createClient();
        $client->request(method: 'GET', uri: '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form input[name="_username"]');
        self::assertSelectorExists('form input[name="_password"]');
        self::assertSelectorExists('form input[name="_csrf_token"]');
    }

    public function testDashboardRedirectsAnonymousVisitorToLogin(): void
    {
        $client = static::createClient();
        $client->request(method: 'GET', uri: '/');

        self::assertResponseRedirects('/login');
    }
}
