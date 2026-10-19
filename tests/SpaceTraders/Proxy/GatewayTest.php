<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders\Proxy;

use App\SpaceTraders\Exception\ApiException;
use App\SpaceTraders\Proxy\Gateway;
use App\SpaceTraders\Proxy\ProxyRequest;
use App\Tests\Entity\UserTestFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class GatewayTest extends TestCase
{
    public function testForwardsTheRequestAndReturnsTheRawResponseOnSuccess(): void
    {
        $httpClient = new MockHttpClient([new MockResponse('{"data":{"foo":"bar"}}', ['http_code' => 200])]);
        $gateway = new Gateway($httpClient);
        $request = new ProxyRequest(
            method: 'GET',
            path: 'my/agent',
            user: UserTestFactory::create(name: 'No Agent', email: 'no-agent@example.com'),
        );

        $response = $gateway->request($request);

        self::assertSame(200, $response->statusCode);
        self::assertSame('{"data":{"foo":"bar"}}', $response->body);
    }

    public function testDoesNotTranslateAnHttpErrorStatusIntoAnException(): void
    {
        // Pass-through contract: a 4xx/5xx from SpaceTraders is relayed as-is,
        // not translated. Only a genuine transport failure is.
        $httpClient = new MockHttpClient([new MockResponse('{"error":"symbol already taken"}', ['http_code' => 422])]);
        $gateway = new Gateway($httpClient);
        $request = new ProxyRequest(
            method: 'POST',
            path: 'register',
            user: UserTestFactory::create(name: 'No Agent', email: 'no-agent@example.com'),
        );

        $response = $gateway->request($request);

        self::assertSame(422, $response->statusCode);
        self::assertSame('{"error":"symbol already taken"}', $response->body);
    }

    public function testTranslatesATransportFailureIntoAApiException(): void
    {
        $httpClient = new MockHttpClient(static function (): never {
            throw new TransportException('Connection reset by peer.');
        });
        $gateway = new Gateway($httpClient);
        $request = new ProxyRequest(
            method: 'GET',
            path: 'my/agent',
            user: UserTestFactory::create(name: 'No Agent', email: 'no-agent@example.com'),
        );

        $this->expectException(ApiException::class);

        $gateway->request($request);
    }
}
