<?php

declare(strict_types=1);

namespace App\SpaceTraders;

use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * What SpaceTradersProxyService hands back to the controller: enough to
 * build the final JsonResponse, nothing HTTP-specific. The controller is the
 * only place that turns this into a Response.
 */
final readonly class SpaceTradersProxyResponseData
{
    /**
     * @param array<string, mixed>           $body
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        public int $statusCode,
        public array $body,
        public array $headers = [],
    ) {
    }

    /**
     * Builds the body from SpaceTraders' own response, merged with whatever
     * extra fields a SpaceTradersResponseAugmenterInterface hook provided.
     * SpaceTraders' own top-level keys (data, error, ...) always win on
     * collision, so they're never overridden by $augmentation->extra.
     */
    public static function fromSpaceTradersResponse(ResponseInterface $spaceTradersResponse, ?SpaceTradersResponseAugmentation $augmentation = null): self
    {
        $decoded = json_decode($spaceTradersResponse->getContent(false), true);
        $extra = null !== $augmentation ? $augmentation->extra : [];
        $headers = null !== $augmentation ? $augmentation->headers : [];
        $body = (\is_array($decoded) ? $decoded : []) + $extra;

        return new self($spaceTradersResponse->getStatusCode(), $body, $headers);
    }
}
