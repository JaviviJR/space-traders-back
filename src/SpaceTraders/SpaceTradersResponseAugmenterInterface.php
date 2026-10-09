<?php

declare(strict_types=1);

namespace App\SpaceTraders;

use App\Entity\Agent;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Optional extension for a SpaceTradersRouteHookInterface hook that wants to
 * enrich the response the front receives without taking over the whole
 * response (that's what beforeForward/afterForward are for). Implementing
 * this is opt-in: most hooks don't need it.
 *
 * $agent is null whenever the request has no X-Agent-Id.
 */
interface SpaceTradersResponseAugmenterInterface
{
    /**
     * Called only when the hook didn't short-circuit or override the
     * response. Return null to add nothing.
     */
    public function augment(Request $request, string $path, ?Agent $agent, User $user, ResponseInterface $spaceTradersResponse): ?SpaceTradersResponseAugmentation;
}
