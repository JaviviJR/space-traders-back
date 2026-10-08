<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders;

use App\SpaceTraders\Exception\SpaceTradersRegistrationException;
use App\SpaceTraders\SpaceTradersClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class SpaceTradersClientTest extends TestCase
{
    public function testRegisterAgentSendsTheExpectedRequestAndDecodesTheResponse(): void
    {
        $responseBody = (string) json_encode([
            'data' => [
                'token' => 'raw-spacetraders-token',
                'agent' => ['symbol' => 'TEST-AGENT'],
            ],
        ]);

        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use ($responseBody): MockResponse {
            self::assertSame('POST', $method);
            self::assertSame('https://api.spacetraders.io/v2/register', $url);

            /** @var array{symbol: string, faction: string} $body */
            $body = json_decode($options['body'], true);
            self::assertSame('TEST-AGENT', $body['symbol']);
            self::assertSame('COSMIC', $body['faction']);

            return new MockResponse($responseBody, ['http_code' => 201]);
        });

        $client = new SpaceTradersClient($httpClient);

        $result = $client->registerAgent('TEST-AGENT', 'COSMIC');

        /** @var array{token: string} $data */
        $data = $result['data'];
        self::assertSame('raw-spacetraders-token', $data['token']);
    }

    public function testRegisterAgentWrapsAnHttpErrorResponse(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse('{"error":"symbol already taken"}', ['http_code' => 422]),
        );

        $client = new SpaceTradersClient($httpClient);

        try {
            $client->registerAgent('TAKEN-AGENT', 'COSMIC');
            self::fail('Expected a SpaceTradersRegistrationException to be thrown.');
        } catch (SpaceTradersRegistrationException $exception) {
            self::assertSame(422, $exception->getStatusCode());
            self::assertSame('{"error":"symbol already taken"}', $exception->getResponseBody());
        }
    }

    public function testRegisterAgentWrapsATransportError(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse('', ['error' => 'Connection failed']),
        );

        $client = new SpaceTradersClient($httpClient);

        try {
            $client->registerAgent('TEST-AGENT', 'COSMIC');
            self::fail('Expected a SpaceTradersRegistrationException to be thrown.');
        } catch (SpaceTradersRegistrationException $exception) {
            self::assertSame(0, $exception->getStatusCode());
            self::assertSame('Connection failed', $exception->getResponseBody());
        }
    }
}
