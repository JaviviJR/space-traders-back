<?php

declare(strict_types=1);

namespace App\SpaceTraders\Proxy;

use App\Http\HttpResponseEnvelope;
use App\SpaceTraders\Exception\ApiException;

interface GatewayInterface
{
    /**
     * @throws ApiException if the call to SpaceTraders fails at the transport level
     */
    public function request(ProxyRequest $proxyRequest): HttpResponseEnvelope;
}
