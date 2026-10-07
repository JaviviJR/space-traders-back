<?php

declare(strict_types=1);

namespace App\Tests\SpaceTraders;

use App\SpaceTraders\Exception\SpaceTradersRegistrationException;
use App\SpaceTraders\SpaceTradersClientInterface;

final class FakeSpaceTradersClient implements SpaceTradersClientInterface
{
    /** @var array<string, mixed>|null */
    private ?array $response = null;

    private ?SpaceTradersRegistrationException $exception = null;

    /**
     * @param array<string, mixed> $response
     */
    public function willReturn(array $response): void
    {
        $this->response = $response;
        $this->exception = null;
    }

    public function willThrow(SpaceTradersRegistrationException $exception): void
    {
        $this->exception = $exception;
        $this->response = null;
    }

    public function registerAgent(string $symbol, string $faction): array
    {
        if (null !== $this->exception) {
            throw $this->exception;
        }

        return $this->response ?? ['data' => ['token' => 'fake-spacetraders-token']];
    }
}
