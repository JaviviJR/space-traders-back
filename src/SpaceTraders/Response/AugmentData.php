<?php

declare(strict_types=1);

namespace App\SpaceTraders\Response;

use App\Http\ResponseData;

/**
 * What an AugmentInterface hook wants to add on top of SpaceTraders' own
 * response: extra top-level body fields (never overriding SpaceTraders' own
 * keys) and/or extra response headers.
 *
 * $extra is a typed ResponseData object, not a raw array, so it can double as
 * the schema source once the OpenAPI describer for proxied routes needs to
 * document these fields.
 */
final readonly class AugmentData
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
