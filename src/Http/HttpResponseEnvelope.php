<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Everything needed to build the final JsonResponse: status code, an
 * already-serialized JSON body, and headers. Both back-only controllers (via
 * EnvelopeResponseFactory::success()/error()) and the SpaceTraders proxy
 * produce this before it is turned into a JsonResponse — no Symfony HTTP
 * type (Request, HttpClient's ResponseInterface) crosses past this point.
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
