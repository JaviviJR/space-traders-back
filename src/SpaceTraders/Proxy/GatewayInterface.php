<?php

declare(strict_types=1);

namespace App\SpaceTraders\Proxy;

use App\Http\HttpResponseEnvelope;
use App\SpaceTraders\Exception\ApiException;

interface GatewayInterface
{
    /**
     * @param array<string, mixed> $options Symfony HttpClient request options (query, headers, body, ...)
     *
     * @throws ApiException if the call to SpaceTraders fails at the transport level
     */
    public function request(string $method, string $path, array $options = []): HttpResponseEnvelope;
}
