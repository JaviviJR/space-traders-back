<?php

declare(strict_types=1);

namespace App\EventListener\ErrorMapper;

/**
 * What an ExceptionErrorMapperInterface produces for ExceptionListener to
 * hand to EnvelopeResponseFactory::error(): the HTTP status alongside the
 * same (code, message) pair ErrorResponseData carries.
 */
final readonly class MappedError
{
    public function __construct(
        public int $status,
        public string $code,
        public string $message,
    ) {
    }
}
