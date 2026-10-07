<?php

declare(strict_types=1);

namespace App\Security\Dto;

final readonly class RefreshTokenPair
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
    ) {
    }
}
