<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatesClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
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

    public function testLogoutWithoutACookieIsANoOp(): void
    {
        $this->browser()
            ->post('/auth/logout')
            ->assertStatus(204)
        ;
    }
}
