<?php

declare(strict_types=1);

namespace App\SpaceTraders;

use App\SpaceTraders\Exception\SpaceTradersRegistrationException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SpaceTradersClient implements SpaceTradersClientInterface
{
    private const BASE_URL = 'https://api.spacetraders.io/v2';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function registerAgent(string $symbol, string $faction): array
    {
        try {
            $response = $this->httpClient->request('POST', self::BASE_URL.'/register', [
                'json' => [
                    'symbol' => $symbol,
                    'faction' => $faction,
                ],
            ]);

            /** @var array<string, mixed> */
            return $response->toArray();
        } catch (HttpExceptionInterface $exception) {
            $response = $exception->getResponse();

            throw new SpaceTradersRegistrationException(
                $response->getStatusCode(),
                $response->getContent(false),
                $exception,
            );
        } catch (ExceptionInterface $exception) {
            throw new SpaceTradersRegistrationException(0, $exception->getMessage(), $exception);
        }
    }
}
