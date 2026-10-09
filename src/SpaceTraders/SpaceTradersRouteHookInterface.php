<?php

declare(strict_types=1);

namespace App\SpaceTraders;

use App\Entity\Agent;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Strategy hook for the SpaceTraders catch-all proxy. Registering a hook
 * only requires implementing this interface; it is auto-tagged, no other
 * wiring is needed.
 *
 * $agent is null when the request has no `X-Agent-Id` header (e.g. agent
 * registration, or an endpoint SpaceTraders doesn't scope to an agent).
 */
#[Autoconfigure(tags: ['app.space_traders_route_hook'])]
interface SpaceTradersRouteHookInterface
{
    public function supports(Request $request, string $path): bool;

    /**
     * Returning a SpaceTradersProxyResponseData short-circuits the call:
     * SpaceTraders is never reached.
     */
    public function beforeForward(Request $request, string $path, ?Agent $agent, User $user): ?SpaceTradersProxyResponseData;

    /**
     * Returning a SpaceTradersProxyResponseData overrides what the front
     * receives; null relays $spaceTradersResponse unchanged (optionally
     * enriched via SpaceTradersResponseAugmenterInterface). Side effects
     * (persisting something, dispatching an event) belong here regardless
     * of the return value.
     */
    public function afterForward(Request $request, string $path, ?Agent $agent, User $user, ResponseInterface $spaceTradersResponse): ?SpaceTradersProxyResponseData;
}
