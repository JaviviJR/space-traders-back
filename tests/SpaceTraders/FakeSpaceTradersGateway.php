<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders;

use App\SpaceTraders\Exception\SpaceTradersApiException;
use App\SpaceTraders\SpaceTradersGatewayInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class FakeSpaceTradersGateway implements SpaceTradersGatewayInterface
{
    private ?ResponseInterface $response = null;

    private ?SpaceTradersApiException $exception = null;

    /**
     * @param array<string, string> $headers
     */
    public function willReturn(string $body, int $statusCode = 200, array $headers = []): void
    {
        // MockResponse must be issued by a MockHttpClient before it can be
        // read, so a single-use client turns it into a real ResponseInterface
        // right away instead of exposing that detail to callers.
        $client = new MockHttpClient([new MockResponse($body, ['http_code' => $statusCode, 'response_headers' => $headers])]);
        $this->response = $client->request('GET', 'https://space-traders.test/');
        $this->exception = null;
    }

    public function willThrow(SpaceTradersApiException $exception): void
    {
        $this->exception = $exception;
        $this->response = null;
    }

    public function request(string $method, string $path, array $options = []): ResponseInterface
    {
        if (null !== $this->exception) {
            throw $this->exception;
        }

        if (null !== $this->response) {
            return $this->response;
        }

        $client = new MockHttpClient([new MockResponse('{"data":{}}')]);

        return $client->request('GET', 'https://space-traders.test/');
    }
}
