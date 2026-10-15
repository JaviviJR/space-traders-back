<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders\Proxy;

use App\Entity\Agent;
use App\Http\HttpResponseEnvelope;
use App\SpaceTraders\Proxy\ProxyRequest;
use App\SpaceTraders\Response\AugmentationSchema;
use App\SpaceTraders\Response\AugmentData;
use App\SpaceTraders\Response\AugmentInterface;
use App\SpaceTraders\RouteHookInterface;

final class FakeRouteHook implements RouteHookInterface, AugmentInterface
{
    private ?HttpResponseEnvelope $shortCircuitResult = null;

    private ?HttpResponseEnvelope $transformedResponse = null;

    private ?AugmentData $augmentation = null;

    private ?AugmentationSchema $augmentationSchema = null;

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

    public function willAugmentWith(AugmentData $augmentation): void
    {
        $this->augmentation = $augmentation;
    }

    public function willDescribeAugmentationWith(AugmentationSchema $schema): void
    {
        $this->augmentationSchema = $schema;
    }

    public function supports(ProxyRequest $request): bool
    {
        return $this->supportedPath === $request->path;
    }

    public function beforeForward(ProxyRequest $request, ?Agent $agent): ?HttpResponseEnvelope
    {
        return $this->shortCircuitResult;
    }

    public function afterForward(ProxyRequest $request, ?Agent $agent, HttpResponseEnvelope $response): HttpResponseEnvelope
    {
        return $this->transformedResponse ?? $response;
    }

    public function augment(ProxyRequest $request, ?Agent $agent, HttpResponseEnvelope $response): ?AugmentData
    {
        return $this->augmentation;
    }

    public function describedAugmentation(): AugmentationSchema
    {
        if (null === $this->augmentationSchema) {
            throw new \LogicException('Call willDescribeAugmentationWith() before describedAugmentation().');
        }

        return $this->augmentationSchema;
    }
}
