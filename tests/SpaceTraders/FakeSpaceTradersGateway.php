<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders;

use App\SpaceTraders\Exception\SpaceTradersApiException;
use App\SpaceTraders\SpaceTradersApiResponse;
use App\SpaceTraders\SpaceTradersGatewayInterface;

final class FakeSpaceTradersGateway implements SpaceTradersGatewayInterface
{
    private ?SpaceTradersApiResponse $response = null;

    private ?SpaceTradersApiException $exception = null;

    /**
     * @param array<string, string[]> $headers
     */
    public function willReturn(string $body, int $statusCode = 200, array $headers = []): void
    {
        $this->response = new SpaceTradersApiResponse($statusCode, $body, $headers);
        $this->exception = null;
    }

    public function willThrow(SpaceTradersApiException $exception): void
    {
        $this->exception = $exception;
        $this->response = null;
    }

    public function request(string $method, string $path, array $options = []): SpaceTradersApiResponse
    {
        if (null !== $this->exception) {
            throw $this->exception;
        }

        if (null !== $this->response) {
            return $this->response;
        }

        return new SpaceTradersApiResponse(200, '{"data":{}}');
    }
}
