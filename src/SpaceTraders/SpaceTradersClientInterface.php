<?php

declare(strict_types=1);

namespace App\SpaceTraders;

use App\SpaceTraders\Exception\SpaceTradersRegistrationException;

interface SpaceTradersClientInterface
{
    /**
     * @return array<string, mixed> the raw decoded SpaceTraders response
     *
     * @throws SpaceTradersRegistrationException if SpaceTraders rejects the registration
     */
    public function registerAgent(string $symbol, string $faction): array;
}
