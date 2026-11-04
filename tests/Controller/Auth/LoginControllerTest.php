<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Entity\RefreshToken;
use App\Factory\UserFactory;
use App\Http\ApiErrorCode;
use App\Security\EventListener\RememberMeChoiceListener;
use App\Tests\Support\ReadsAccessToken;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\HttpFoundation\Cookie;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\ResetDatabase;

use function Zenstruck\Foundry\Persistence\repository;

#[ResetDatabase]
final class LoginControllerTest extends WebTestCase
{
    use ClockSensitiveTrait;
    use HasBrowser;
    use ReadsAccessToken;

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

    public function testTheAccessTokenExpiresFifteenMinutesAfterItIsIssued(): void
    {
        UserFactory::createOne(['email' => 'login-token-ttl@example.com']);

        $browser = $this->attemptLogin('login-token-ttl@example.com', 'password123')->assertStatus(200);

        $accessToken = $browser->json()->search('data.accessToken');
        self::assertIsString($accessToken);
        self::assertSame(900, $this->accessTokenLifetime($accessToken));
    }

    public function testLoginWithTheWrongPasswordIsAnInvalidCredentialsError(): void
    {
        UserFactory::createOne(['email' => 'login-wrong-password@example.com']);

        $browser = $this->attemptLogin('login-wrong-password@example.com', 'not-the-right-password')
            ->assertStatus(401)
            ->assertHeaderEquals('WWW-Authenticate', null)
        ;

        $this->assertError($browser, ApiErrorCode::InvalidCredentials, 'Invalid email or password.');
    }

    public function testLoginWithAnUnknownEmailIsTheSameInvalidCredentialsError(): void
    {
        $browser = $this->attemptLogin('login-never-registered@example.com', 'password123')->assertStatus(401);

        $this->assertError($browser, ApiErrorCode::InvalidCredentials, 'Invalid email or password.');
    }

    public function testLoginWithAnUnverifiedEmailIsAnEmailNotVerifiedError(): void
    {
        UserFactory::createOne(['email' => 'login-unverified@example.com', 'isVerified' => false]);

        $browser = $this->attemptLogin('login-unverified@example.com', 'password123')->assertStatus(403);

        $this->assertError($browser, ApiErrorCode::EmailNotVerified, 'Email address is not verified.');
    }

    public function testLoginWithAnUnverifiedEmailAndTheWrongPasswordDoesNotRevealTheAccountState(): void
    {
        UserFactory::createOne(['email' => 'login-unverified-wrong@example.com', 'isVerified' => false]);

        $browser = $this->attemptLogin('login-unverified-wrong@example.com', 'not-the-right-password')->assertStatus(401);

        $this->assertError($browser, ApiErrorCode::InvalidCredentials, 'Invalid email or password.');
    }

    public function testLoginWithANonJsonBodyIsAnUnsupportedMediaTypeError(): void
    {
        $browser = $this->browser()
            ->post('/auth/login', HttpOptions::create()
                ->withHeader('Content-Type', 'text/plain')
                ->withBody('email=login@example.com&password=password123'))
            ->assertStatus(415)
        ;

        $this->assertError($browser, ApiErrorCode::HttpError, 'The request body must be JSON.');
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function malformedRememberMeProvider(): iterable
    {
        yield 'text' => ['yes'];
        yield 'number' => [1];
        yield 'explicit null' => [null];
    }

    #[DataProvider('malformedRememberMeProvider')]
    public function testLoginWithAMalformedRememberMeIsAnHttpErrorWithoutSession(mixed $rememberMe): void
    {
        UserFactory::createOne(['email' => 'login-malformed-remember@example.com']);

        $browser = $this->attemptLogin('login-malformed-remember@example.com', 'password123', [RememberMeChoiceListener::FIELD => $rememberMe])
            ->assertStatus(400)
        ;

        $this->assertError($browser, ApiErrorCode::HttpError, 'The key "rememberMe" must be a boolean.');
        self::assertSame([], $browser->client()->getResponse()->headers->getCookies());
        repository(RefreshToken::class)->assert()->empty();
    }

    #[DataProvider('malformedRememberMeProvider')]
    public function testMalformedRememberMeWinsOverAWrongPassword(mixed $rememberMe): void
    {
        UserFactory::createOne(['email' => 'login-malformed-remember-wrong@example.com']);

        $browser = $this->attemptLogin('login-malformed-remember-wrong@example.com', 'not-the-right-password', [RememberMeChoiceListener::FIELD => $rememberMe])
            ->assertStatus(400)
        ;

        $this->assertError($browser, ApiErrorCode::HttpError, 'The key "rememberMe" must be a boolean.');
    }

    #[DataProvider('malformedRememberMeProvider')]
    public function testMalformedRememberMeWinsOverAnUnverifiedEmail(mixed $rememberMe): void
    {
        UserFactory::createOne(['email' => 'login-malformed-remember-unverified@example.com', 'isVerified' => false]);

        $browser = $this->attemptLogin('login-malformed-remember-unverified@example.com', 'password123', [RememberMeChoiceListener::FIELD => $rememberMe])
            ->assertStatus(400)
        ;

        $this->assertError($browser, ApiErrorCode::HttpError, 'The key "rememberMe" must be a boolean.');
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function testLoginAcceptsABooleanRememberMe(bool $rememberMe): void
    {
        UserFactory::createOne(['email' => 'login-boolean-remember@example.com']);

        $this->attemptLogin('login-boolean-remember@example.com', 'password123', [RememberMeChoiceListener::FIELD => $rememberMe])
            ->assertStatus(200)
        ;
    }

    public function testRememberMeIssuesAPersistentCookieAndAThirtyDaySession(): void
    {
        $now = self::mockTime()->now();
        UserFactory::createOne(['email' => 'login-remember-me@example.com']);

        $browser = $this->attemptLogin('login-remember-me@example.com', 'password123', [RememberMeChoiceListener::FIELD => true])
            ->assertStatus(200)
        ;

        $expectedExpiry = $now->modify('+30 days')->getTimestamp();
        $cookie = $this->refreshCookie($browser);
        self::assertSame($expectedExpiry, $cookie->getExpiresTime());
        self::assertSame('/auth', $cookie->getPath());
        self::assertTrue($cookie->isSecure());
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame(Cookie::SAMESITE_STRICT, $cookie->getSameSite());

        self::assertSame([$expectedExpiry], $this->storedExpiries());
    }

    /**
     * @param array<string, bool> $rememberMe
     */
    #[TestWith([[]], 'missing')]
    #[TestWith([[RememberMeChoiceListener::FIELD => false]], 'false')]
    public function testWithoutRememberMeTheCookieIsASessionCookieAndTheSessionLastsTwentyFourHours(array $rememberMe): void
    {
        $now = self::mockTime()->now();
        UserFactory::createOne(['email' => 'login-default-mode@example.com']);

        $browser = $this->attemptLogin('login-default-mode@example.com', 'password123', $rememberMe)
            ->assertStatus(200)
        ;

        $this->assertSessionCookie($browser);
        self::assertSame([$now->modify('+24 hours')->getTimestamp()], $this->storedExpiries());
    }

    public function testANewLoginWithAPreviousCookieStartsANewSessionAndLeavesTheOldOneAlone(): void
    {
        $now = self::mockTime()->now();
        UserFactory::createOne(['email' => 'login-previous-cookie@example.com']);
        $browser = $this->browser();

        $browser->post('/auth/login', HttpOptions::json([
            'email' => 'login-previous-cookie@example.com',
            'password' => 'password123',
            RememberMeChoiceListener::FIELD => true,
        ]))->assertStatus(200);

        // The browser still holds the persistent cookie and sends it along (Path=/auth).
        self::assertNotNull($browser->client()->getCookieJar()->get('refresh_token', '/auth'));

        $browser->post('/auth/login', HttpOptions::json([
            'email' => 'login-previous-cookie@example.com',
            'password' => 'password123',
        ]))->assertStatus(200);

        $this->assertSessionCookie($browser);

        $expiries = $this->storedExpiries();
        sort($expiries);
        self::assertSame([$now->modify('+24 hours')->getTimestamp(), $now->modify('+30 days')->getTimestamp()], $expiries);
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function attemptLogin(string $email, string $password, array $extra = []): KernelBrowser
    {
        return $this->browser()->post('/auth/login', HttpOptions::json(['email' => $email, 'password' => $password] + $extra));
    }

    private function assertError(KernelBrowser $browser, ApiErrorCode $code, string $message): void
    {
        self::assertSame(['error' => ['code' => $code->value, 'message' => $message]], $browser->json()->decoded());
    }

    private function refreshCookie(KernelBrowser $browser): Cookie
    {
        $cookie = $browser->client()->getResponse()->headers->getCookies()[0] ?? null;
        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertSame('refresh_token', $cookie->getName());

        return $cookie;
    }

    private function assertSessionCookie(KernelBrowser $browser): void
    {
        self::assertSame(0, $this->refreshCookie($browser)->getExpiresTime());

        $header = (string) $browser->client()->getResponse()->headers->get('Set-Cookie');
        self::assertStringContainsString('refresh_token=', $header);
        self::assertStringNotContainsStringIgnoringCase('expires=', $header);
        self::assertStringNotContainsStringIgnoringCase('max-age', $header);
    }

    /**
     * @return list<int>
     */
    private function storedExpiries(): array
    {
        return array_map(
            static fn (RefreshToken $token): int => $token->getExpiresAt()->getTimestamp(),
            repository(RefreshToken::class)->findAll(),
        );
    }
}
