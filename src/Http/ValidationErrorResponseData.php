<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Error body for a failed validation: the usual code and message plus
 * $violations, keyed by Symfony's property path as-is (`email`,
 * `address.street`, `items[0].name`) with every message for that path, so
 * the front can attach each one to its form field.
 */
final readonly class ValidationErrorResponseData extends ErrorResponseData
{
    /**
     * @param array<string, list<string>> $violations
     */
    public function __construct(
        ErrorCode $code,
        string $message,
        public array $violations,
    ) {
        parent::__construct($code, $message);
    }
}
