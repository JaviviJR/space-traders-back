<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders;

use App\Http\HttpResponseEnvelope;
use App\SpaceTraders\EnvelopeMergeException;
use App\SpaceTraders\SpaceTradersEnvelopeBuilder;
use App\SpaceTraders\SpaceTradersResponseExtraData;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class SpaceTradersEnvelopeBuilderTest extends TestCase
{
    public function testPassesTheBodyThroughUntouchedWithoutExtraData(): void
    {
        $builder = new SpaceTradersEnvelopeBuilder($this->objectSerializer());
        $response = new HttpResponseEnvelope(201, '{"data":{"foo":"bar"}}');

        $result = $builder->build($response, null);

        self::assertSame(201, $result->statusCode);
        self::assertSame('{"data":{"foo":"bar"}}', $result->body);
        self::assertSame([], $result->headers);
    }

    public function testPassesTheBodyThroughUntouchedWhenExtraDataHasNoExtra(): void
    {
        $builder = new SpaceTradersEnvelopeBuilder($this->objectSerializer());
        $response = new HttpResponseEnvelope(200, '{"data":{"foo":"bar"}}');
        $extraData = new SpaceTradersResponseExtraData(headers: ['X-Agent-Id' => '42']);

        $result = $builder->build($response, $extraData);

        self::assertSame('{"data":{"foo":"bar"}}', $result->body);
        self::assertSame(['X-Agent-Id' => '42'], $result->headers);
    }

    public function testMergesExtraAsSiblingFields(): void
    {
        $builder = new SpaceTradersEnvelopeBuilder($this->objectSerializer());
        $response = new HttpResponseEnvelope(200, '{"data":{"foo":"bar"}}');
        $extraData = new SpaceTradersResponseExtraData(new MetaAugmentationData(['agentId' => 42]));

        $result = $builder->build($response, $extraData);

        self::assertSame(['data' => ['foo' => 'bar'], 'meta' => ['agentId' => 42]], json_decode($result->body, true));
    }

    public function testThrowsWhenExtraCollidesWithAnUpstreamKey(): void
    {
        $builder = new SpaceTradersEnvelopeBuilder($this->objectSerializer());
        $response = new HttpResponseEnvelope(200, '{"data":{"foo":"bar"},"meta":{"existing":true}}');
        $extraData = new SpaceTradersResponseExtraData(new MetaAugmentationData(['agentId' => 42]));

        $this->expectException(EnvelopeMergeException::class);

        $builder->build($response, $extraData);
    }

    public function testThrowsWhenTheUpstreamBodyIsNotAValidJsonObject(): void
    {
        $builder = new SpaceTradersEnvelopeBuilder($this->objectSerializer());
        $response = new HttpResponseEnvelope(200, 'not json');
        $extraData = new SpaceTradersResponseExtraData(new MetaAugmentationData(['agentId' => 42]));

        $this->expectException(EnvelopeMergeException::class);

        $builder->build($response, $extraData);
    }

    private function objectSerializer(): NormalizerInterface
    {
        return new Serializer([new ObjectNormalizer()], ['json' => new JsonEncoder()]);
    }
}
