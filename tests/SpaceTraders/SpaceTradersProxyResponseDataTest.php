<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders;

use App\SpaceTraders\SpaceTradersProxyResponseData;
use App\SpaceTraders\SpaceTradersResponseAugmentation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class SpaceTradersProxyResponseDataTest extends TestCase
{
    public function testMirrorsStatusAndBodyWithoutAnAugmentation(): void
    {
        $spaceTradersResponse = $this->spaceTradersResponse('{"data":{"foo":"bar"}}', 201);

        $result = SpaceTradersProxyResponseData::fromSpaceTradersResponse($spaceTradersResponse);

        self::assertSame(201, $result->statusCode);
        self::assertSame(['data' => ['foo' => 'bar']], $result->body);
        self::assertSame([], $result->headers);
    }

    public function testMergesAugmentationExtraAsSiblingFields(): void
    {
        $spaceTradersResponse = $this->spaceTradersResponse('{"data":{"foo":"bar"}}', 200);
        $augmentation = new SpaceTradersResponseAugmentation(extra: ['meta' => ['agentId' => 42]]);

        $result = SpaceTradersProxyResponseData::fromSpaceTradersResponse($spaceTradersResponse, $augmentation);

        self::assertSame(['data' => ['foo' => 'bar'], 'meta' => ['agentId' => 42]], $result->body);
    }

    public function testAugmentationCannotOverrideSpaceTradersOwnKeys(): void
    {
        $spaceTradersResponse = $this->spaceTradersResponse('{"data":{"foo":"bar"}}', 200);
        $augmentation = new SpaceTradersResponseAugmentation(extra: ['data' => ['tampered' => true]]);

        $result = SpaceTradersProxyResponseData::fromSpaceTradersResponse($spaceTradersResponse, $augmentation);

        self::assertSame(['data' => ['foo' => 'bar']], $result->body);
    }

    public function testCopiesHeadersFromTheAugmentation(): void
    {
        $spaceTradersResponse = $this->spaceTradersResponse('{"data":{}}', 200);
        $augmentation = new SpaceTradersResponseAugmentation(headers: ['X-Agent-Id' => '42']);

        $result = SpaceTradersProxyResponseData::fromSpaceTradersResponse($spaceTradersResponse, $augmentation);

        self::assertSame(['X-Agent-Id' => '42'], $result->headers);
    }

    private function spaceTradersResponse(string $body, int $statusCode): \Symfony\Contracts\HttpClient\ResponseInterface
    {
        $client = new MockHttpClient([new MockResponse($body, ['http_code' => $statusCode])]);

        return $client->request('GET', 'https://space-traders.test/');
    }
}
