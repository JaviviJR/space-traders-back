<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders;

use App\SpaceTraders\Exception\SpaceTradersApiException;
use App\SpaceTraders\SpaceTradersGateway;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class SpaceTradersGatewayTest extends TestCase
{
    public function testForwardsTheRequestAndReturnsTheRawResponseOnSuccess(): void
    {
        $httpClient = new MockHttpClient([new MockResponse('{"data":{"foo":"bar"}}', ['http_code' => 200])]);
        $gateway = new SpaceTradersGateway($httpClient);

        $response = $gateway->request('GET', 'my/agent');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('{"data":{"foo":"bar"}}', $response->getContent());
    }

    public function testDoesNotTranslateAnHttpErrorStatusIntoAnException(): void
    {
        // Pass-through contract: a 4xx/5xx from SpaceTraders is relayed as-is,
        // not translated. Only a genuine transport failure is.
        $httpClient = new MockHttpClient([new MockResponse('{"error":"symbol already taken"}', ['http_code' => 422])]);
        $gateway = new SpaceTradersGateway($httpClient);

        $response = $gateway->request('POST', 'register');

        self::assertSame(422, $response->getStatusCode());
        self::assertSame('{"error":"symbol already taken"}', $response->getContent(false));
    }

    public function testTranslatesATransportFailureIntoASpaceTradersApiException(): void
    {
        $httpClient = new MockHttpClient(static function (): never {
            throw new TransportException('Connection reset by peer.');
        });
        $gateway = new SpaceTradersGateway($httpClient);

        $this->expectException(SpaceTradersApiException::class);

        $gateway->request('GET', 'my/agent');
    }
}
