<?php

declare(strict_types=1);

namespace App\SpaceTraders\Exception;

final class ApiException extends \RuntimeException
{
    public function __construct(
        private readonly int $statusCode,
        private readonly string $responseBody,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('SpaceTraders API call failed with status %d.', $statusCode),
            0,
            $previous,
        );
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }
}
