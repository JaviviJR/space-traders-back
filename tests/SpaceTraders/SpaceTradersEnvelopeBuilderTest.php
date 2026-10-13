<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders;

use App\Http\HttpResponseEnvelope;
use App\SpaceTraders\SpaceTradersAugmentationData;
use App\SpaceTraders\SpaceTradersEnvelopeBuilder;
use App\SpaceTraders\SpaceTradersResponseAugmentation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

final class SpaceTradersEnvelopeBuilderTest extends TestCase
{
    public function testPassesTheBodyThroughUntouchedWithoutAnAugmentation(): void
    {
        $builder = new SpaceTradersEnvelopeBuilder($this->objectSerializer());
        $response = new HttpResponseEnvelope(201, '{"data":{"foo":"bar"}}');

        $result = $builder->build($response, null);

        self::assertSame(201, $result->statusCode);
        self::assertSame('{"data":{"foo":"bar"}}', $result->body);
        self::assertSame([], $result->headers);
    }

    public function testMergesAugmentationExtraAsSiblingFields(): void
    {
        $builder = new SpaceTradersEnvelopeBuilder($this->objectSerializer());
        $response = new HttpResponseEnvelope(200, '{"data":{"foo":"bar"}}');
        $augmentation = new SpaceTradersResponseAugmentation(extra: new MetaAugmentationData(['agentId' => 42]));

        $result = $builder->build($response, $augmentation);

        self::assertSame(['data' => ['foo' => 'bar'], 'meta' => ['agentId' => 42]], json_decode($result->body, true));
    }

    public function testAugmentationCannotOverrideSpaceTradersOwnKeys(): void
    {
        $builder = new SpaceTradersEnvelopeBuilder($this->objectSerializer());
        $response = new HttpResponseEnvelope(200, '{"data":{"foo":"bar"}}');
        $augmentation = new SpaceTradersResponseAugmentation(extra: $this->tamperedData());

        $result = $builder->build($response, $augmentation);

        self::assertSame(['data' => ['foo' => 'bar']], json_decode($result->body, true));
    }

    public function testCopiesHeadersFromTheAugmentation(): void
    {
        $builder = new SpaceTradersEnvelopeBuilder($this->objectSerializer());
        $response = new HttpResponseEnvelope(200, '{"data":{}}');
        $augmentation = new SpaceTradersResponseAugmentation(headers: ['X-Agent-Id' => '42']);

        $result = $builder->build($response, $augmentation);

        self::assertSame(['X-Agent-Id' => '42'], $result->headers);
    }

    private function objectSerializer(): SerializerInterface
    {
        return new Serializer([new ObjectNormalizer()], ['json' => new JsonEncoder()]);
    }

    private function tamperedData(): SpaceTradersAugmentationData
    {
        return new class implements SpaceTradersAugmentationData {
            /** @var array<string, bool> */
            public array $data = ['tampered' => true];
        };
    }
}
