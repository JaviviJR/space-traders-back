<?php

declare(strict_types=1);

namespace App\SpaceTraders;

use App\Http\ResponseData;

/**
 * What a SpaceTradersResponseAugmenterInterface hook wants to add on top of
 * SpaceTraders' own response: extra top-level body fields (never overriding
 * SpaceTraders' own keys) and/or extra response headers.
 *
 * $extra is a typed ResponseData object, not a raw array, so it can double as
 * the schema source once the OpenAPI describer for proxied routes needs to
 * document these fields.
 */
final readonly class SpaceTradersResponseExtraData
{
    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        public ?ResponseData $extra = null,
        public array $headers = [],
    ) {
    }
}
