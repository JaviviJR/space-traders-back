<?php

declare(strict_types=1);

namespace App\SpaceTraders;

use App\Http\HttpResponseEnvelope;
use App\SpaceTraders\Exception\SpaceTradersApiException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SpaceTradersGateway implements SpaceTradersGatewayInterface
{
    private const BASE_URL = 'https://api.spacetraders.io/v2';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function request(string $method, string $path, array $options = []): HttpResponseEnvelope
    {
        try {
            $response = $this->httpClient->request($method, self::BASE_URL.'/'.ltrim($path, '/'), $options);

            // `false` on both calls: a 4xx/5xx from SpaceTraders is relayed
            // as-is (pass-through contract), never translated into a thrown
            // exception. Only a genuine transport failure is.
            //
            // The HttpClient ResponseInterface never leaves the gateway: it's
            // lazy (getStatusCode()/getContent()/getHeaders() can each throw
            // a transport exception), so resolving it eagerly here, where
            // that exception is already caught, keeps every caller free of
            // that concern.
            return new HttpResponseEnvelope(
                $response->getStatusCode(),
                $response->getContent(false),
                $response->getHeaders(false),
            );
        } catch (TransportExceptionInterface $exception) {
            throw new SpaceTradersApiException(0, $exception->getMessage(), $exception);
        }
    }
}
