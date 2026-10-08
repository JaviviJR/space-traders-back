<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatesClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Cookie;

final class LoginControllerTest extends WebTestCase
{
    use AuthenticatesClient;

    public function testLoginReturnsAnAccessTokenAndSetsTheRefreshCookie(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'login-happy@example.com']);

        $client->jsonRequest('POST', '/auth/login', [
            'email' => 'login-happy@example.com',
            'password' => 'password123',
        ]);

        self::assertResponseIsSuccessful();

        /** @var array{data: array{accessToken: string}} $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertNotEmpty($data['data']['accessToken']);

        $cookie = $client->getResponse()->headers->getCookies()[0] ?? null;
        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertSame('refresh_token', $cookie->getName());
        self::assertSame('/auth', $cookie->getPath());
        self::assertTrue($cookie->isHttpOnly());
        self::assertTrue($cookie->isSecure());
        self::assertSame(Cookie::SAMESITE_STRICT, $cookie->getSameSite());
    }

    public function testLoginWithTheWrongPasswordReturnsUnauthorized(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'login-wrong-password@example.com']);

        $client->jsonRequest('POST', '/auth/login', [
            'email' => 'login-wrong-password@example.com',
            'password' => 'not-the-right-password',
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testLoginWithAnUnverifiedEmailReturnsForbidden(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'login-unverified@example.com', 'isVerified' => false]);

        $client->jsonRequest('POST', '/auth/login', [
            'email' => 'login-unverified@example.com',
            'password' => 'password123',
        ]);

        self::assertResponseStatusCodeSame(403);
    }
}
