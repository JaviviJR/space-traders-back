<?php

declare(strict_types=1);

namespace App\SpaceTraders;

/**
 * Marker for objects a SpaceTradersResponseAugmenterInterface hook can return
 * as SpaceTradersResponseAugmentation::$extra. The boundary between internal
 * domain objects and what a hook is allowed to add on top of SpaceTraders'
 * own response body — and, once a route's OpenAPI description merges it in,
 * the schema source for those extra fields (the way ResponseData already is
 * for back-only endpoints).
 */
interface SpaceTradersAugmentationData
{
}
