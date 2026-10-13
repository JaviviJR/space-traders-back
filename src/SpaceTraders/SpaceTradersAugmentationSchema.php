<?php

declare(strict_types=1);

namespace App\SpaceTraders;

use App\Http\ResponseData;

/**
 * Doc-time metadata only: which SpaceTraders operation a
 * SpaceTradersResponseAugmenterInterface hook adds fields to, and the
 * ResponseData class those fields come from. The OpenAPI describer for
 * proxied routes uses this to merge the extra properties into the documented
 * response schema.
 *
 * Deliberately separate from SpaceTradersRouteHookInterface::supports(),
 * which matches a real SpaceTradersProxyRequest (and needs a real User) at
 * request time. $path/$method here duplicate that routing decision in a
 * small, doc-only way rather than forcing the describer to fabricate a
 * request just to ask a hook what it supports.
 */
final readonly class SpaceTradersAugmentationSchema
{
    /**
     * @param class-string<ResponseData> $dataClass
     */
    public function __construct(
        public string $method,
        public string $path,
        public string $dataClass,
    ) {
    }
}
