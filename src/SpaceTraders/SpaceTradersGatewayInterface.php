<?php

declare(strict_types=1);

namespace App\SpaceTraders;

use App\SpaceTraders\Exception\SpaceTradersApiException;

interface SpaceTradersGatewayInterface
{
    /**
     * @param array<string, mixed> $options Symfony HttpClient request options (query, headers, body, ...)
     *
     * @throws SpaceTradersApiException if the call to SpaceTraders fails at the transport level
     */
    public function request(string $method, string $path, array $options = []): SpaceTradersApiResponse;
}
