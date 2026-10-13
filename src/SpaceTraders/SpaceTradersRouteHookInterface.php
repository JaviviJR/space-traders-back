<?php

declare(strict_types=1);

namespace App\SpaceTraders;

use App\Entity\Agent;
use App\Http\HttpResponseEnvelope;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Strategy hook for the SpaceTraders catch-all proxy. Registering a hook
 * only requires implementing this interface; it is auto-tagged, no other
 * wiring is needed.
 *
 * $agent is null when the request has no `X-Agent-Id` header (e.g. agent
 * registration, or an endpoint SpaceTraders doesn't scope to an agent). The
 * authenticated user is always available via $request->user.
 */
#[Autoconfigure(tags: ['app.space_traders_route_hook'])]
interface SpaceTradersRouteHookInterface
{
    public function supports(SpaceTradersProxyRequest $request): bool;

    /**
     * Returning a HttpResponseEnvelope short-circuits the call: SpaceTraders
     * is never reached.
     */
    public function beforeForward(SpaceTradersProxyRequest $request, ?Agent $agent): ?HttpResponseEnvelope;

    /**
     * Transforms the real SpaceTraders response on its way to the front
     * (e.g. redacting a field inside `data`). Return the same
     * HttpResponseEnvelope unchanged when there's nothing to do — unlike
     * beforeForward, this never short-circuits anything: SpaceTraders has
     * already been called by the time this runs. Side effects (persisting
     * something, dispatching an event) belong here too, regardless of
     * whether the response itself changes.
     */
    public function afterForward(SpaceTradersProxyRequest $request, ?Agent $agent, HttpResponseEnvelope $response): HttpResponseEnvelope;
}
