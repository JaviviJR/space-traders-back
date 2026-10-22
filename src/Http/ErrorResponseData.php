<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Sibling of a successful ResponseData under the `error` top-level key.
 * $code is a stable, machine-readable identifier for the front to branch on
 * (typed as ApiErrorCode so the generated spec lists every possible value);
 * $message is human-readable and safe to expose (never a raw exception
 * message for unexpected failures).
 *
 * Not final: an error kind that needs extra fields (e.g.
 * ValidationErrorResponseData) extends it, so every error body still carries
 * code and message and the rest of the pipeline treats it as any other.
 */
readonly class ErrorResponseData implements ResponseData
{
    public function __construct(
        public ApiErrorCode $code,
        public string $message,
    ) {
    }
}
