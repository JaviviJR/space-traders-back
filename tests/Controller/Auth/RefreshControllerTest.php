<?php

declare(strict_types=1);

namespace App\Tests\Controller\Auth;

use App\Entity\RefreshToken;
use App\Factory\UserFactory;
use App\Tests\Support\AuthenticatesClient;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\ResetDatabase;

use function Zenstruck\Foundry\Persistence\repository;

#[ResetDatabase]
final class RefreshControllerTest extends WebTestCase
{
    use AuthenticatesClient;
    use ClockSensitiveTrait;
    use HasBrowser;

    private const ONE_DAY = 24 * 3600;
    private const THIRTY_DAYS = 30 * self::ONE_DAY;

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

    /**
     * The clock is frozen at the real current time, not a fixed date: the
     * test client's cookie jar drops cookies by the system time, so a
     * persistent cookie issued at a past date would never be sent.
     */
    #[TestWith([null, self::ONE_DAY - 1], 'default mode, 1s before 24h')]
    #[TestWith([false, self::ONE_DAY - 1], 'rememberMe false, 1s before 24h')]
    #[TestWith([true, self::ONE_DAY + 1], 'rememberMe, 1s after 24h')]
    #[TestWith([true, self::THIRTY_DAYS - 1], 'rememberMe, 1s before 30 days')]
    public function testRefreshIsAcceptedBeforeTheSessionExpires(?bool $rememberMe, int $elapsedSeconds): void
    {
        $browser = $this->loginAndWait('refresh-still-valid@example.com', $rememberMe, $elapsedSeconds);

        $browser->post('/auth/refresh')->assertSuccessful();
    }

    #[TestWith([null, self::ONE_DAY + 1], 'default mode, 1s after 24h')]
    #[TestWith([false, self::ONE_DAY + 1], 'rememberMe false, 1s after 24h')]
    #[TestWith([true, self::THIRTY_DAYS + 1], 'rememberMe, 1s after 30 days')]
    public function testRefreshIsRejectedAndTheCookieClearedOnceTheSessionExpires(?bool $rememberMe, int $elapsedSeconds): void
    {
        $browser = $this->loginAndWait('refresh-expired@example.com', $rememberMe, $elapsedSeconds);

        $browser
            ->post('/auth/refresh')
            ->assertStatus(401)
            ->assertHeaderContains('Set-Cookie', 'refresh_token=deleted')
            ->assertHeaderContains('Set-Cookie', 'Max-Age=0')
            ->assertJsonMatches('error.code', 'invalid_refresh_token')
        ;
    }

    public function testRefreshingNeitherResetsTheCookieNorExtendsTheSession(): void
    {
        $clock = self::mockTime();
        $now = $clock->now();
        $browser = $this->browser();
        UserFactory::createOne(['email' => 'refresh-no-extension@example.com']);
        $this->login($browser, 'refresh-no-extension@example.com');

        foreach ([3600, 3600, 3600] as $seconds) {
            $clock->sleep($seconds);
            $browser
                ->post('/auth/refresh')
                ->assertSuccessful()
                ->assertHeaderEquals('Set-Cookie', null)
            ;
        }

        // Read from the database, not from Doctrine's identity map.
        static::getContainer()->get(EntityManagerInterface::class)->clear();
        $tokens = repository(RefreshToken::class)->findAll();
        self::assertCount(1, $tokens);
        self::assertSame($now->modify('+24 hours')->getTimestamp(), $tokens[0]->getExpiresAt()->getTimestamp());
    }

    private function loginAndWait(string $email, ?bool $rememberMe, int $elapsedSeconds): KernelBrowser
    {
        $clock = self::mockTime();
        $browser = $this->browser();
        UserFactory::createOne(['email' => $email]);
        $this->login($browser, $email, rememberMe: $rememberMe);

        $clock->sleep($elapsedSeconds);

        return $browser;
    }
}
