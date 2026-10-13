<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders;

use App\Entity\Agent;
use App\Http\HttpResponseEnvelope;
use App\SpaceTraders\SpaceTradersAugmentationSchema;
use App\SpaceTraders\SpaceTradersProxyRequest;
use App\SpaceTraders\SpaceTradersResponseExtraData;
use App\SpaceTraders\SpaceTradersResponseCustomDataInterface;
use App\SpaceTraders\SpaceTradersRouteHookInterface;

final class FakeSpaceTradersRouteHook implements SpaceTradersRouteHookInterface, SpaceTradersResponseCustomDataInterface
{
    private ?HttpResponseEnvelope $shortCircuitResult = null;

    private ?HttpResponseEnvelope $transformedResponse = null;

    private ?SpaceTradersResponseExtraData $augmentation = null;

    private ?SpaceTradersAugmentationSchema $augmentationSchema = null;

    public function __construct(private readonly string $supportedPath)
    {
    }

    public function willShortCircuitWith(HttpResponseEnvelope $result): void
    {
        $this->shortCircuitResult = $result;
    }

    public function willTransformResponseWith(HttpResponseEnvelope $response): void
    {
        $this->transformedResponse = $response;
    }

    public function willAugmentWith(SpaceTradersResponseExtraData $augmentation): void
    {
        $this->augmentation = $augmentation;
    }

    public function willDescribeAugmentationWith(SpaceTradersAugmentationSchema $schema): void
    {
        $this->augmentationSchema = $schema;
    }

    public function supports(SpaceTradersProxyRequest $request): bool
    {
        return $this->supportedPath === $request->path;
    }

    public function beforeForward(SpaceTradersProxyRequest $request, ?Agent $agent): ?HttpResponseEnvelope
    {
        return $this->shortCircuitResult;
    }

    public function afterForward(SpaceTradersProxyRequest $request, ?Agent $agent, HttpResponseEnvelope $response): HttpResponseEnvelope
    {
        return $this->transformedResponse ?? $response;
    }

    public function augment(SpaceTradersProxyRequest $request, ?Agent $agent, HttpResponseEnvelope $response): ?SpaceTradersResponseExtraData
    {
        return $this->augmentation;
    }

    public function describedAugmentation(): SpaceTradersAugmentationSchema
    {
        if (null === $this->augmentationSchema) {
            throw new \LogicException('Call willDescribeAugmentationWith() before describedAugmentation().');
        }

        return $this->augmentationSchema;
    }
}
