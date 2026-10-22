<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatesClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
final class RefreshControllerTest extends WebTestCase
{
    use AuthenticatesClient;
    use HasBrowser;

    public function testRefreshWithAValidCookieReturnsANewAccessToken(): void
    {
        $browser = $this->browser();
        UserFactory::createOne(['email' => 'refresh-happy@example.com']);
        $this->login($browser, 'refresh-happy@example.com');

        // The refresh token is not rotating: the same cookie keeps working.
        $browser->post('/auth/refresh')->assertSuccessful();

        /** @var array{data: array{accessToken: string}} $data */
        $data = $browser->json()->decoded();
        self::assertNotEmpty($data['data']['accessToken']);

        $browser->post('/auth/refresh')->assertSuccessful();
    }

    public function testRefreshWithoutACookieReturnsUnauthorizedAndClearsTheCookie(): void
    {
        $this->browser()
            ->post('/auth/refresh')
            ->assertStatus(401)
            ->assertHeaderContains('Set-Cookie', 'refresh_token=deleted')
            ->assertHeaderContains('Set-Cookie', 'Max-Age=0')
            ->assertJsonMatches('error.code', 'invalid_refresh_token')
        ;
    }
}
