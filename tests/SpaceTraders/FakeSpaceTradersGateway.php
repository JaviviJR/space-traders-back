<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders;

use App\Http\HttpResponseEnvelope;
use App\SpaceTraders\Exception\SpaceTradersApiException;
use App\SpaceTraders\SpaceTradersGatewayInterface;

final class FakeSpaceTradersGateway implements SpaceTradersGatewayInterface
{
    private ?HttpResponseEnvelope $response = null;

    private ?SpaceTradersApiException $exception = null;

    private ?string $lastPath = null;

    /**
     * @param array<string, string[]> $headers
     */
    public function willReturn(string $body, int $statusCode = 200, array $headers = []): void
    {
        $this->response = new HttpResponseEnvelope($statusCode, $body, $headers);
        $this->exception = null;
    }

    public function willThrow(SpaceTradersApiException $exception): void
    {
        $this->exception = $exception;
        $this->response = null;
    }

    public function lastPath(): ?string
    {
        return $this->lastPath;
    }

    public function request(string $method, string $path, array $options = []): HttpResponseEnvelope
    {
        $this->lastPath = $path;

        if (null !== $this->exception) {
            throw $this->exception;
        }

        if (null !== $this->response) {
            return $this->response;
        }

        return new HttpResponseEnvelope(200, '{"data":{}}');
    }
}
