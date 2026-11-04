<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
final class AuthCorsTest extends WebTestCase
{
    use HasBrowser;

    private const string ALLOWED_ORIGIN = 'http://localhost:5173';
    private const string DISALLOWED_ORIGIN = 'http://evil.example';

    /**
     * @return iterable<string, array{string}>
     */
    public static function authEndpoints(): iterable
    {
        yield 'login' => ['/auth/login'];
        yield 'refresh' => ['/auth/refresh'];
        yield 'logout' => ['/auth/logout'];
    }

    #[DataProvider('authEndpoints')]
    public function testPreflightFromAnAllowedOriginAllowsCredentials(string $path): void
    {
        $this->browser()
            ->request('OPTIONS', $path, $this->preflight(self::ALLOWED_ORIGIN, 'POST'))
            ->assertSuccessful()
            ->assertHeaderEquals('Access-Control-Allow-Origin', self::ALLOWED_ORIGIN)
            ->assertHeaderEquals('Access-Control-Allow-Credentials', 'true')
        ;
    }

    public function testLoginRefreshAndLogoutFromAnAllowedOriginAllowCredentials(): void
    {
        $browser = $this->browser();
        UserFactory::createOne(['email' => 'cors-allowed@example.com']);

        $browser
            ->post('/auth/login', HttpOptions::json([
                'email' => 'cors-allowed@example.com',
                'password' => 'password123',
            ])->withHeader('Origin', self::ALLOWED_ORIGIN))
            ->assertStatus(200)
            ->assertHeaderContains('Set-Cookie', 'refresh_token=')
        ;
        $this->assertCredentialsAllowedFor($browser, self::ALLOWED_ORIGIN);

        $browser->post('/auth/refresh', $this->fromOrigin(self::ALLOWED_ORIGIN))->assertStatus(200);
        $this->assertCredentialsAllowedFor($browser, self::ALLOWED_ORIGIN);

        $browser->post('/auth/logout', $this->fromOrigin(self::ALLOWED_ORIGIN))->assertStatus(204);
        $this->assertCredentialsAllowedFor($browser, self::ALLOWED_ORIGIN);
    }

    #[DataProvider('authEndpoints')]
    public function testPreflightFromADisallowedOriginDoesNotAllowTheOrigin(string $path): void
    {
        // NelmioCors sets Allow-Credentials before checking the origin; browsers ignore it without Allow-Origin (Fetch "CORS check").
        $this->browser()
            ->request('OPTIONS', $path, $this->preflight(self::DISALLOWED_ORIGIN, 'POST'))
            ->assertHeaderEquals('Access-Control-Allow-Origin', null)
        ;
    }

    public function testARequestFromADisallowedOriginGetsNoCorsHeaders(): void
    {
        $browser = $this->browser();
        UserFactory::createOne(['email' => 'cors-disallowed@example.com']);

        $browser
            ->post('/auth/login', HttpOptions::json([
                'email' => 'cors-disallowed@example.com',
                'password' => 'password123',
            ])->withHeader('Origin', self::DISALLOWED_ORIGIN))
            ->assertStatus(200)
            ->assertHeaderEquals('Access-Control-Allow-Origin', null)
            ->assertHeaderEquals('Access-Control-Allow-Credentials', null)
        ;
    }

    public function testPreflightOutsideAuthAllowsTheOriginWithoutCredentials(): void
    {
        $this->browser()
            ->request('OPTIONS', '/api/agents', $this->preflight(self::ALLOWED_ORIGIN, 'GET'))
            ->assertSuccessful()
            ->assertHeaderEquals('Access-Control-Allow-Origin', self::ALLOWED_ORIGIN)
            ->assertHeaderEquals('Access-Control-Allow-Credentials', null)
        ;
    }

    private function assertCredentialsAllowedFor(KernelBrowser $browser, string $origin): void
    {
        $browser
            ->assertHeaderEquals('Access-Control-Allow-Origin', $origin)
            ->assertHeaderEquals('Access-Control-Allow-Credentials', 'true')
        ;
    }

    private function preflight(string $origin, string $method): HttpOptions
    {
        return $this->fromOrigin($origin)->withHeader('Access-Control-Request-Method', $method);
    }

    private function fromOrigin(string $origin): HttpOptions
    {
        return HttpOptions::create()->withHeader('Origin', $origin);
    }
}
