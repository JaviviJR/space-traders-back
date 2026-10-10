<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders;

use App\Entity\Agent;
use App\Http\HttpResponseEnvelope;
use App\SpaceTraders\SpaceTradersApiResponse;
use App\SpaceTraders\SpaceTradersProxyRequest;
use App\SpaceTraders\SpaceTradersResponseAugmentation;
use App\SpaceTraders\SpaceTradersResponseAugmenterInterface;
use App\SpaceTraders\SpaceTradersRouteHookInterface;

final class FakeSpaceTradersRouteHook implements SpaceTradersRouteHookInterface, SpaceTradersResponseAugmenterInterface
{
    private ?HttpResponseEnvelope $shortCircuitResult = null;

    private ?SpaceTradersApiResponse $transformedResponse = null;

    private ?SpaceTradersResponseAugmentation $augmentation = null;

    public function __construct(private readonly string $supportedPath)
    {
    }

    public function willShortCircuitWith(HttpResponseEnvelope $result): void
    {
        $this->shortCircuitResult = $result;
    }

    public function willTransformResponseWith(SpaceTradersApiResponse $response): void
    {
        $this->transformedResponse = $response;
    }

    public function willAugmentWith(SpaceTradersResponseAugmentation $augmentation): void
    {
        $this->augmentation = $augmentation;
    }

    public function supports(SpaceTradersProxyRequest $request): bool
    {
        return $this->supportedPath === $request->path;
    }

    public function beforeForward(SpaceTradersProxyRequest $request, ?Agent $agent): ?HttpResponseEnvelope
    {
        return $this->shortCircuitResult;
    }

    public function afterForward(SpaceTradersProxyRequest $request, ?Agent $agent, SpaceTradersApiResponse $response): SpaceTradersApiResponse
    {
        return $this->transformedResponse ?? $response;
    }

    public function augment(SpaceTradersProxyRequest $request, ?Agent $agent, SpaceTradersApiResponse $response): ?SpaceTradersResponseAugmentation
    {
        return $this->augmentation;
    }
}
