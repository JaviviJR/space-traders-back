<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders;

use App\SpaceTraders\SpaceTradersAugmentationData;
use App\SpaceTraders\SpaceTradersProxyResponseData;
use App\SpaceTraders\SpaceTradersResponseAugmentation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

final class SpaceTradersProxyResponseDataTest extends TestCase
{
    public function testMirrorsStatusAndBodyWithoutAnAugmentation(): void
    {
        $spaceTradersResponse = $this->spaceTradersResponse('{"data":{"foo":"bar"}}', 201);

        $result = SpaceTradersProxyResponseData::fromSpaceTradersResponse($spaceTradersResponse, null, $this->objectSerializer());

        self::assertSame(201, $result->statusCode);
        self::assertSame(['data' => ['foo' => 'bar']], $result->body);
        self::assertSame([], $result->headers);
    }

    public function testMergesAugmentationExtraAsSiblingFields(): void
    {
        $spaceTradersResponse = $this->spaceTradersResponse('{"data":{"foo":"bar"}}', 200);
        $augmentation = new SpaceTradersResponseAugmentation(extra: $this->metaData(['agentId' => 42]));

        $result = SpaceTradersProxyResponseData::fromSpaceTradersResponse($spaceTradersResponse, $augmentation, $this->objectSerializer());

        self::assertSame(['data' => ['foo' => 'bar'], 'meta' => ['agentId' => 42]], $result->body);
    }

    public function testAugmentationCannotOverrideSpaceTradersOwnKeys(): void
    {
        $spaceTradersResponse = $this->spaceTradersResponse('{"data":{"foo":"bar"}}', 200);
        $augmentation = new SpaceTradersResponseAugmentation(extra: $this->tamperedData());

        $result = SpaceTradersProxyResponseData::fromSpaceTradersResponse($spaceTradersResponse, $augmentation, $this->objectSerializer());

        self::assertSame(['data' => ['foo' => 'bar']], $result->body);
    }

    public function testCopiesHeadersFromTheAugmentation(): void
    {
        $spaceTradersResponse = $this->spaceTradersResponse('{"data":{}}', 200);
        $augmentation = new SpaceTradersResponseAugmentation(headers: ['X-Agent-Id' => '42']);

        $result = SpaceTradersProxyResponseData::fromSpaceTradersResponse($spaceTradersResponse, $augmentation, $this->objectSerializer());

        self::assertSame(['X-Agent-Id' => '42'], $result->headers);
    }

    private function spaceTradersResponse(string $body, int $statusCode): \Symfony\Contracts\HttpClient\ResponseInterface
    {
        $client = new MockHttpClient([new MockResponse($body, ['http_code' => $statusCode])]);

        return $client->request('GET', 'https://space-traders.test/');
    }

    private function objectSerializer(): SerializerInterface
    {
        return new Serializer([new ObjectNormalizer()], ['json' => new JsonEncoder()]);
    }

    /**
     * @param array<string, mixed> $meta
     */
    /**
     * @param array<string, mixed> $meta
     */
    private function metaData(array $meta): SpaceTradersAugmentationData
    {
        return new class($meta) implements SpaceTradersAugmentationData {
            /**
             * @param array<string, mixed> $meta
             */
            public function __construct(public array $meta)
            {
            }
        };
    }

    private function tamperedData(): SpaceTradersAugmentationData
    {
        return new class implements SpaceTradersAugmentationData {
            /** @var array<string, bool> */
            public array $data = ['tampered' => true];
        };
    }
}
