<?php

declare(strict_types=1);

namespace App\EventListener\ErrorMapper;

use App\Http\ErrorResponseData;

/**
 * What an ExceptionErrorMapperInterface produces for ExceptionListener to
 * hand to EnvelopeResponseFactory::error(): the public body, as-is, plus
 * the HTTP status, which belongs to the response line rather than the body.
 */
final readonly class MappedError
{
    public function __construct(
        public int $status,
        public ErrorResponseData $body,
    ) {
    }
}
