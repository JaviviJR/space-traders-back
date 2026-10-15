<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders\Proxy;

use App\Http\HttpResponseEnvelope;
use App\SpaceTraders\Exception\ApiException;
use App\SpaceTraders\Proxy\GatewayInterface;
use App\SpaceTraders\Proxy\ProxyRequest;

final class FakeGateway implements GatewayInterface
{
    private ?HttpResponseEnvelope $response = null;

    private ?ApiException $exception = null;

    private ?string $lastPath = null;

    /**
     * @param array<string, string[]> $headers
     */
    public function willReturn(string $body, int $statusCode = 200, array $headers = []): void
    {
        $this->response = new HttpResponseEnvelope($statusCode, $body, $headers);
        $this->exception = null;
    }

    public function willThrow(ApiException $exception): void
    {
        $this->exception = $exception;
        $this->response = null;
    }

    public function lastPath(): ?string
    {
        return $this->lastPath;
    }

    public function request(ProxyRequest $proxyRequest): HttpResponseEnvelope
    {
        $this->lastPath = $proxyRequest->path;

        if (null !== $this->exception) {
            throw $this->exception;
        }

        if (null !== $this->response) {
            return $this->response;
        }

        return new HttpResponseEnvelope(200, '{"data":{}}');
    }
}
