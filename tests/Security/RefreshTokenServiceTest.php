<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Factory\UserFactory;
use App\Security\Exception\InvalidRefreshTokenException;
use App\Security\RefreshTokenService;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
final class RefreshTokenServiceTest extends KernelTestCase
{
    use ClockSensitiveTrait;

    private const NOW = '2026-01-01 00:00:00';

    public function testVerifyingATokenThatWasNeverIssuedThrows(): void
    {
        self::bootKernel();
        $service = static::getContainer()->get(RefreshTokenService::class);

        $this->expectException(InvalidRefreshTokenException::class);

        $service->verify('this-token-was-never-issued');
    }

    public function testRevokingAllTokensForAUserInvalidatesThem(): void
    {
        self::bootKernel();
        $user = UserFactory::createOne(['email' => 'refresh-revoke-all@example.com']);

        $service = static::getContainer()->get(RefreshTokenService::class);
        $pair = $service->issue($user, false);

        // Sanity check: the token is valid before revocation.
        self::assertSame($user->getEmail(), $service->verify($pair->refreshToken)->getEmail());

        $service->revokeAllForUser($user);

        $this->expectException(InvalidRefreshTokenException::class);

        $service->verify($pair->refreshToken);
    }

    #[TestWith([true, '2026-01-31 00:00:00'])]
    #[TestWith([false, '2026-01-02 00:00:00'])]
    public function testIssueSetsTheExpiryForTheChosenMode(bool $rememberMe, string $expectedExpiry): void
    {
        self::mockTime(self::NOW);
        self::bootKernel();
        $user = UserFactory::createOne(['email' => 'refresh-expiry-mode@example.com']);

        $pair = static::getContainer()->get(RefreshTokenService::class)->issue($user, $rememberMe);

        self::assertSame((new \DateTimeImmutable($expectedExpiry))->getTimestamp(), $pair->refreshTokenExpiresAt->getTimestamp());
    }

    public function testADefaultModeTokenIsStillValidOneSecondBeforeTwentyFourHours(): void
    {
        $clock = self::mockTime(self::NOW);
        self::bootKernel();
        $user = UserFactory::createOne(['email' => 'refresh-default-before@example.com']);
        $service = static::getContainer()->get(RefreshTokenService::class);
        $pair = $service->issue($user, false);

        $clock->sleep(24 * 3600 - 1);

        self::assertSame($user->getEmail(), $service->verify($pair->refreshToken)->getEmail());
    }

    public function testADefaultModeTokenIsRejectedOneSecondAfterTwentyFourHours(): void
    {
        $clock = self::mockTime(self::NOW);
        self::bootKernel();
        $user = UserFactory::createOne(['email' => 'refresh-default-after@example.com']);
        $service = static::getContainer()->get(RefreshTokenService::class);
        $pair = $service->issue($user, false);

        $clock->sleep(24 * 3600 + 1);

        $this->expectException(InvalidRefreshTokenException::class);

        $service->verify($pair->refreshToken);
    }
}
