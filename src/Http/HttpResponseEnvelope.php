<?php

declare(strict_types=1);

namespace App\Http;

/**
 * A raw HTTP-shaped response: status code, body (a string — ours once
 * serialized, or SpaceTraders' own, verbatim, before any merge decision is
 * made), and headers. Used at both ends of the SpaceTraders proxy pipeline —
 * what App\SpaceTraders\Proxy\GatewayInterface::request() hands back, and what
 * EnvelopeResponseFactory::envelope() produces from it — and as the final
 * shape back-only controllers (via EnvelopeResponseFactory::success()/
 * error()) and the proxy controller hand to
 * EnvelopeResponseFactory::toJsonResponse(). No Symfony HTTP type (Request,
 * HttpClient's ResponseInterface) crosses past this point.
 */
final readonly class HttpResponseEnvelope
{
    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        public int $statusCode,
        public string $body,
        public array $headers = [],
    ) {
    }
}
