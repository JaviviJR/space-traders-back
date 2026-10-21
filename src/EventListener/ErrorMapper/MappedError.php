<?php

declare(strict_types=1);

namespace App\EventListener\ErrorMapper;

use App\Http\ErrorResponseData;

/**
 * What an ExceptionErrorMapperInterface produces for ExceptionListener to
 * hand to EnvelopeResponseFactory::error(): the public body, as-is, plus
 * what belongs to the response rather than the body — the HTTP status and
 * any headers the error needs (e.g. a Set-Cookie clearing a rejected token).
 */
final readonly class MappedError
{
    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        public int $status,
        public ErrorResponseData $body,
        public array $headers = [],
    ) {
    }
}
