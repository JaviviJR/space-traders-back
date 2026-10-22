<?php

declare(strict_types=1);

namespace App\Http;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

/**
 * Error body for a failed validation: the usual code and message plus
 * $violations, keyed by Symfony's property path as-is (`email`,
 * `address.street`, `items[0].name`) with every message for that path, so
 * the front can attach each one to its form field.
 *
 * The #[OA\Schema] documents it as `allOf` ApiError, so the front's codegen
 * sees it extends ApiError. `code` and `message` still show up again inside
 * that `allOf`: Nelmio describes every property, inherited ones included,
 * and no attribute stops it.
 */
#[OA\Schema(allOf: [new OA\Schema(ref: new Model(type: ErrorResponseData::class))])]
final readonly class ValidationErrorResponseData extends ErrorResponseData
{
    /**
     * @param array<string, list<string>> $violations
     */
    public function __construct(
        ApiErrorCode $code,
        string $message,
        public array $violations,
    ) {
        parent::__construct($code, $message);
    }
}
