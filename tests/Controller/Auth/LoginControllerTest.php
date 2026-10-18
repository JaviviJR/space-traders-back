<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatesClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
final class LoginControllerTest extends WebTestCase
{
    use AuthenticatesClient;
    use HasBrowser;

    public function testLoginReturnsAnAccessTokenAndSetsTheRefreshCookie(): void
    {
        $browser = $this->browser();
        UserFactory::createOne(['email' => 'login-happy@example.com']);

        $browser
            ->post('/auth/login', HttpOptions::json([
                'email' => 'login-happy@example.com',
                'password' => 'password123',
            ]))
            ->assertSuccessful()
        ;

        /** @var array{data: array{accessToken: string}} $data */
        $data = $browser->json()->decoded();
        self::assertNotEmpty($data['data']['accessToken']);

        $cookie = $browser->client()->getResponse()->headers->getCookies()[0] ?? null;
        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertSame('refresh_token', $cookie->getName());
        self::assertSame('/auth', $cookie->getPath());
        self::assertTrue($cookie->isHttpOnly());
        self::assertTrue($cookie->isSecure());
        self::assertSame(Cookie::SAMESITE_STRICT, $cookie->getSameSite());
    }

    public function testLoginWithTheWrongPasswordReturnsUnauthorized(): void
    {
        UserFactory::createOne(['email' => 'login-wrong-password@example.com']);

        $this->browser()
            ->post('/auth/login', HttpOptions::json([
                'email' => 'login-wrong-password@example.com',
                'password' => 'not-the-right-password',
            ]))
            ->assertStatus(401)
        ;
    }

    public function testLoginWithAnUnverifiedEmailReturnsForbidden(): void
    {
        UserFactory::createOne(['email' => 'login-unverified@example.com', 'isVerified' => false]);

        $this->browser()
            ->post('/auth/login', HttpOptions::json([
                'email' => 'login-unverified@example.com',
                'password' => 'password123',
            ]))
            ->assertStatus(403)
        ;
    }
}
