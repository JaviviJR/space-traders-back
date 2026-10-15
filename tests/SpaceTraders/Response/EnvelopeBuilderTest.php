<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders\Response;

use App\Http\HttpResponseEnvelope;
use App\SpaceTraders\Response\AugmentData;
use App\SpaceTraders\Response\HttpEnvelopeMerger;
use App\SpaceTraders\Response\EnvelopeMergeException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class EnvelopeBuilderTest extends TestCase
{
    public function testPassesTheBodyThroughUntouchedWithoutExtraData(): void
    {
        $builder = new HttpEnvelopeMerger($this->objectSerializer());
        $response = new HttpResponseEnvelope(201, '{"data":{"foo":"bar"}}');

        $result = $builder->merge($response, null);

        self::assertSame(201, $result->statusCode);
        self::assertSame('{"data":{"foo":"bar"}}', $result->body);
        self::assertSame([], $result->headers);
    }

    public function testPassesTheBodyThroughUntouchedWhenExtraDataHasNoExtra(): void
    {
        $builder = new HttpEnvelopeMerger($this->objectSerializer());
        $response = new HttpResponseEnvelope(200, '{"data":{"foo":"bar"}}');
        $extraData = new AugmentData(headers: ['X-Agent-Id' => '42']);

        $result = $builder->merge($response, $extraData);

        self::assertSame('{"data":{"foo":"bar"}}', $result->body);
        self::assertSame(['X-Agent-Id' => '42'], $result->headers);
    }

    public function testMergesExtraAsSiblingFields(): void
    {
        $builder = new HttpEnvelopeMerger($this->objectSerializer());
        $response = new HttpResponseEnvelope(200, '{"data":{"foo":"bar"}}');
        $extraData = new AugmentData(new MetaAugmentationData(['agentId' => 42]));

        $result = $builder->merge($response, $extraData);

        self::assertSame(['data' => ['foo' => 'bar'], 'meta' => ['agentId' => 42]], json_decode($result->body, true));
    }

    public function testThrowsWhenExtraCollidesWithAnUpstreamKey(): void
    {
        $builder = new HttpEnvelopeMerger($this->objectSerializer());
        $response = new HttpResponseEnvelope(200, '{"data":{"foo":"bar"},"meta":{"existing":true}}');
        $extraData = new AugmentData(new MetaAugmentationData(['agentId' => 42]));

        $this->expectException(EnvelopeMergeException::class);

        $builder->merge($response, $extraData);
    }

    public function testThrowsWhenTheUpstreamBodyIsNotAValidJsonObject(): void
    {
        $builder = new HttpEnvelopeMerger($this->objectSerializer());
        $response = new HttpResponseEnvelope(200, 'not json');
        $extraData = new AugmentData(new MetaAugmentationData(['agentId' => 42]));

        $this->expectException(EnvelopeMergeException::class);

        $builder->merge($response, $extraData);
    }

    private function objectSerializer(): NormalizerInterface
    {
        return new Serializer([new ObjectNormalizer()], ['json' => new JsonEncoder()]);
    }
}
