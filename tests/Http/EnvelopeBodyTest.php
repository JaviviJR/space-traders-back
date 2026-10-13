<?php

declare(strict_types=1);

namespace App\Tests\Http;

use App\Http\EnvelopeBody;
use App\Http\EnvelopeMergeException;
use PHPUnit\Framework\TestCase;

final class EnvelopeBodyTest extends TestCase
{
    public function testPassesTheUpstreamBodyThroughUntouchedWithoutExtra(): void
    {
        $body = new EnvelopeBody('{"data":{"foo":"bar"}}');

        self::assertSame('{"data":{"foo":"bar"}}', $body->toJson());
    }

    public function testExtraAloneIsTheWholeBodyWithoutAnUpstream(): void
    {
        $body = new EnvelopeBody(null, ['data' => ['foo' => 'bar']]);

        self::assertSame(['data' => ['foo' => 'bar']], json_decode($body->toJson(), true));
    }

    public function testMergesExtraAsSiblingFieldsOfTheUpstreamBody(): void
    {
        $body = new EnvelopeBody('{"data":{"foo":"bar"}}', ['meta' => ['agentId' => 42]]);

        self::assertSame(['data' => ['foo' => 'bar'], 'meta' => ['agentId' => 42]], json_decode($body->toJson(), true));
    }

    public function testThrowsWhenExtraCollidesWithAnUpstreamKey(): void
    {
        $body = new EnvelopeBody('{"data":{"foo":"bar"}}', ['data' => ['tampered' => true]]);

        $this->expectException(EnvelopeMergeException::class);

        $body->toJson();
    }

    public function testThrowsWhenTheUpstreamBodyIsNotAValidJsonObject(): void
    {
        $body = new EnvelopeBody('not json', ['meta' => ['agentId' => 42]]);

        $this->expectException(EnvelopeMergeException::class);

        $body->toJson();
    }
}
