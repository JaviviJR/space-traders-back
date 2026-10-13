<?php

declare(strict_types=1);

namespace App\SpaceTraders;

use App\Entity\Agent;
use App\Http\HttpResponseEnvelope;

/**
 * Optional extension for a SpaceTradersRouteHookInterface hook that wants to
 * enrich the response the front receives without taking over the whole
 * response — that's what afterForward is for, and it can't touch
 * SpaceTraders' own top-level keys anyway. Implementing this is opt-in:
 * most hooks don't need it.
 *
 * $agent is null whenever the request has no X-Agent-Id.
 */
interface SpaceTradersResponseCustomDataInterface
{
    /**
     * Called with the HttpResponseEnvelope afterForward produced. Return
     * null to add nothing.
     */
    public function augment(SpaceTradersProxyRequest $request, ?Agent $agent, HttpResponseEnvelope $response): ?SpaceTradersResponseExtraData;

    /**
     * Doc-time counterpart to augment(): which operation this hook documents
     * extra fields for, and the ResponseData class they come from. Called by
     * the OpenAPI describer for proxied routes, never at request time.
     */
    public function describedAugmentation(): SpaceTradersAugmentationSchema;
}
