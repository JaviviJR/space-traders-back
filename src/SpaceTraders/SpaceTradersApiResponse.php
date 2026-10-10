<?php

declare(strict_types=1);

namespace App\SpaceTraders;

/**
 * What SpaceTradersGatewayInterface::request() hands back. The HttpClient
 * ResponseInterface never leaves the gateway: it's lazy (getStatusCode()/
 * getContent()/getHeaders() can each throw a transport exception), so
 * resolving it eagerly inside the gateway, where that exception is already
 * caught, keeps every caller free of that concern. $body is left undecoded;
 * hooks that need structured data decode it themselves.
 */
final readonly class SpaceTradersApiResponse
{
    /**
     * @param array<string, string[]> $headers
     */
    public function __construct(
        public int $statusCode,
        public string $body,
        public array $headers = [],
    ) {
    }
}
