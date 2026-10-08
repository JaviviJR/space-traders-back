<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders;

use App\SpaceTraders\SpaceTradersResponseRelay;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class SpaceTradersResponseRelayTest extends TestCase
{
    public function testMirrorsStatusAndBody(): void
    {
        $httpClient = new MockHttpClient([new MockResponse('{"data":{"foo":"bar"}}', ['http_code' => 201])]);
        $spaceTradersResponse = $httpClient->request('GET', 'https://space-traders.test/');

        $response = (new SpaceTradersResponseRelay())->toSymfonyResponse($spaceTradersResponse);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('{"data":{"foo":"bar"}}', $response->getContent());
    }

    public function testStripsTransportOnlyHeaders(): void
    {
        $httpClient = new MockHttpClient([new MockResponse('{}', [
            'http_code' => 200,
            'response_headers' => [
                'content-type: application/json',
                'content-length: 2',
                'transfer-encoding: chunked',
                'connection: keep-alive',
            ],
        ])]);
        $spaceTradersResponse = $httpClient->request('GET', 'https://space-traders.test/');

        $response = (new SpaceTradersResponseRelay())->toSymfonyResponse($spaceTradersResponse);

        self::assertTrue($response->headers->has('content-type'));
        self::assertFalse($response->headers->has('transfer-encoding'));
        self::assertFalse($response->headers->has('connection'));
    }
}
