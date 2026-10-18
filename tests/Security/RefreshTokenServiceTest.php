<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Factory\UserFactory;
use App\Security\Exception\InvalidRefreshTokenException;
use App\Security\RefreshTokenService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
final class RefreshTokenServiceTest extends KernelTestCase
{
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
        $pair = $service->issue($user);

        // Sanity check: the token is valid before revocation.
        self::assertSame($user->getEmail(), $service->verify($pair->refreshToken)->getEmail());

        $service->revokeAllForUser($user);

        $this->expectException(InvalidRefreshTokenException::class);

        $service->verify($pair->refreshToken);
    }
}
