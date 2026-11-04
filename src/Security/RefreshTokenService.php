<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use App\Security\Dto\RefreshTokenPair;
use App\Security\Exception\InvalidRefreshTokenException;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Clock\ClockInterface;

final class RefreshTokenService
{
    private const REMEMBER_ME_TTL = '+30 days';
    private const DEFAULT_TTL = '+24 hours';

    public function __construct(
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly JWTTokenManagerInterface $jwtTokenManager,
        private readonly ClockInterface $clock,
    ) {
    }

    public function issue(User $user, bool $rememberMe): RefreshTokenPair
    {
        $plaintext = bin2hex(random_bytes(32));
        $expiresAt = $this->clock->now()->modify($rememberMe ? self::REMEMBER_ME_TTL : self::DEFAULT_TTL);

        $refreshToken = new RefreshToken($user, $this->hash($plaintext), $expiresAt);

        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();

        return new RefreshTokenPair($this->createAccessToken($user), $plaintext, $expiresAt);
    }

    /**
     * @throws InvalidRefreshTokenException if the token is missing, expired or revoked
     */
    public function verify(string $plaintext): User
    {
        $refreshToken = $this->refreshTokenRepository->findByTokenHash($this->hash($plaintext));

        if (null === $refreshToken || !$refreshToken->isValid($this->clock->now())) {
            throw InvalidRefreshTokenException::becauseNotFoundOrInvalid();
        }

        return $refreshToken->getUser();
    }

    public function revokeByPlaintext(string $plaintext): void
    {
        $refreshToken = $this->refreshTokenRepository->findByTokenHash($this->hash($plaintext));

        if (null === $refreshToken) {
            return;
        }

        $refreshToken->revoke();
        $this->entityManager->flush();
    }

    public function revokeAllForUser(User $user): void
    {
        foreach ($this->refreshTokenRepository->findActiveForUser($user) as $refreshToken) {
            $refreshToken->revoke();
        }

        $this->entityManager->flush();
    }

    public function createAccessToken(User $user): string
    {
        return $this->jwtTokenManager->create($user);
    }

    private function hash(string $plaintext): string
    {
        return hash('sha256', $plaintext);
    }
}
