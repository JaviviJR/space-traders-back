<?php

declare(strict_types=1);

namespace App\OpenApi\Attribute;

use App\Http\ErrorResponseData;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

/**
 * `#[OA\Response]` for the `error` side of EnvelopeResponseFactory::error():
 * every back-only error response shares the same ErrorResponseData shape,
 * so this only needs a status and a description, not a model per call site.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class ErrorResponse extends OA\Response
{
    public function __construct(int $status, string $description = '')
    {
        parent::__construct(
            response: $status,
            description: $description,
            content: new OA\JsonContent(
                properties: [new OA\Property(property: 'error', ref: new Model(type: ErrorResponseData::class))],
            ),
        );
    }
}
