<?php

declare(strict_types=1);

namespace App\SpaceTraders;

/**
 * What a SpaceTradersResponseAugmenterInterface hook wants to add on top of
 * SpaceTraders' own response: extra top-level body fields (never overriding
 * SpaceTraders' own keys) and/or extra response headers.
 */
final readonly class SpaceTradersResponseAugmentation
{
    /**
     * @param array<string, mixed>        $extra
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        public array $extra = [],
        public array $headers = [],
    ) {
    }
}
