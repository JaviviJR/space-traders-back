<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatesClient;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
final class LogoutControllerTest extends WebTestCase
{
    use AuthenticatesClient;
    use HasBrowser;

    public function testLogoutRevokesOnlyTheSessionItWasCalledFrom(): void
    {
        // WebTestCase only supports booting one kernel per test, so two
        // simultaneous sessions for the same user are simulated with a
        // single browser, swapping which refresh cookie is active in its
        // cookie jar between requests.
        $email = 'logout-two-sessions@example.com';
        $browser = $this->browser();

        UserFactory::createOne(['email' => $email]);
        $this->login($browser, $email);
        $cookieA = $browser->client()->getCookieJar()->get('refresh_token', '/auth');
        self::assertNotNull($cookieA);

        $browser->client()->getCookieJar()->clear();
        $this->login($browser, $email);
        $cookieB = $browser->client()->getCookieJar()->get('refresh_token', '/auth');
        self::assertNotNull($cookieB);
        self::assertNotSame($cookieA->getValue(), $cookieB->getValue());

        $browser->client()->getCookieJar()->clear();
        $browser->client()->getCookieJar()->set($cookieA);
        $browser->post('/auth/logout')->assertStatus(204);

        // Session A's cookie was cleared by the logout response itself.
        $browser->post('/auth/refresh')->assertStatus(401);

        $browser->client()->getCookieJar()->clear();
        $browser->client()->getCookieJar()->set($cookieB);
        $browser->post('/auth/refresh')->assertSuccessful();
    }

    #[TestWith([null], 'default mode')]
    #[TestWith([true], 'rememberMe')]
    public function testLogoutRevokesTheSessionAndClearsTheCookieInEitherMode(?bool $rememberMe): void
    {
        $email = 'logout-either-mode@example.com';
        $browser = $this->browser();
        UserFactory::createOne(['email' => $email]);
        $this->login($browser, $email, rememberMe: $rememberMe);
        $cookie = $browser->client()->getCookieJar()->get('refresh_token', '/auth');
        self::assertNotNull($cookie);

        $browser
            ->post('/auth/logout')
            ->assertStatus(204)
            ->assertHeaderContains('Set-Cookie', 'refresh_token=deleted')
            ->assertHeaderContains('Set-Cookie', 'Max-Age=0')
        ;

        // Present the old cookie again: the session itself is revoked server side.
        $browser->client()->getCookieJar()->set($cookie);
        $browser->post('/auth/refresh')->assertStatus(401);
    }

    public function testLogoutWithAnUnknownCookieIsANoOp(): void
    {
        $browser = $this->browser();
        $browser->client()->getCookieJar()->set(new Cookie('refresh_token', 'this-token-was-never-issued', path: '/auth'));

        $browser
            ->post('/auth/logout')
            ->assertStatus(204)
        ;

        // Otherwise this would pass as the no-cookie case.
        self::assertSame('this-token-was-never-issued', $browser->client()->getRequest()->cookies->get('refresh_token'));
    }

    public function testLogoutWithoutACookieIsANoOp(): void
    {
        $this->browser()
            ->post('/auth/logout')
            ->assertStatus(204)
        ;
    }
}
