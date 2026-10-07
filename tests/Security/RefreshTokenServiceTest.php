<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\Exception\InvalidRefreshTokenException;
use App\Security\RefreshTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

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
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = new User('refresh-revoke-all@example.com', 'hashed-password');
        $entityManager->persist($user);
        $entityManager->flush();

        $service = static::getContainer()->get(RefreshTokenService::class);
        $pair = $service->issue($user);

        // Sanity check: the token is valid before revocation.
        self::assertSame($user->getEmail(), $service->verify($pair->refreshToken)->getEmail());

        $service->revokeAllForUser($user);

        $this->expectException(InvalidRefreshTokenException::class);

        $service->verify($pair->refreshToken);
    }
}
