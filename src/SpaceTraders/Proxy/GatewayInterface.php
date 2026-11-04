<?php

declare(strict_types=1);

namespace App\SpaceTraders\Proxy;

use App\Http\HttpResponseEnvelope;
use App\SpaceTraders\Exception\ApiException;

/**
 * The single way this back calls SpaceTraders, from the proxy or from any
 * other service: never inject HttpClientInterface for it. SpaceTraders
 * rate-limits per agent, and this is the one choke point to add that limiting
 * to.
 *
 * Kept as an interface despite having one implementation (Gateway) because
 * it's the test seam the proxy and hook tests fake against (FakeGateway),
 * instead of spinning up MockHttpClient each time.
 */
interface GatewayInterface
{
    /**
     * @throws ApiException if the call to SpaceTraders fails at the transport level
     */
    public function request(ProxyRequest $proxyRequest): HttpResponseEnvelope;
}
