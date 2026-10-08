<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatesClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LogoutControllerTest extends WebTestCase
{
    use AuthenticatesClient;

    public function testLogoutRevokesOnlyTheSessionItWasCalledFrom(): void
    {
        // WebTestCase only supports booting one kernel per test, so two
        // simultaneous sessions for the same user are simulated with a
        // single client, swapping which refresh cookie is active in its
        // cookie jar between requests.
        $email = 'logout-two-sessions@example.com';
        $client = static::createClient();

        UserFactory::createOne(['email' => $email]);
        $this->login($client, $email);
        $cookieA = $client->getCookieJar()->get('refresh_token', '/auth');
        self::assertNotNull($cookieA);

        $client->getCookieJar()->clear();
        $this->login($client, $email);
        $cookieB = $client->getCookieJar()->get('refresh_token', '/auth');
        self::assertNotNull($cookieB);
        self::assertNotSame($cookieA->getValue(), $cookieB->getValue());

        $client->getCookieJar()->clear();
        $client->getCookieJar()->set($cookieA);
        $client->jsonRequest('POST', '/auth/logout');
        self::assertResponseStatusCodeSame(204);

        // Session A's cookie was cleared by the logout response itself.
        $client->jsonRequest('POST', '/auth/refresh');
        self::assertResponseStatusCodeSame(401);

        $client->getCookieJar()->clear();
        $client->getCookieJar()->set($cookieB);
        $client->jsonRequest('POST', '/auth/refresh');
        self::assertResponseIsSuccessful();
    }

    public function testLogoutWithoutACookieIsANoOp(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/auth/logout');

        self::assertResponseStatusCodeSame(204);
    }
}
