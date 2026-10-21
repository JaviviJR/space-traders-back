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
 * An error kind whose body extends ErrorResponseData with extra fields
 * passes that subclass as $model so its schema shows them.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class ErrorResponse extends OA\Response
{
    /**
     * @param class-string<ErrorResponseData> $model
     */
    public function __construct(int $status, string $description = '', string $model = ErrorResponseData::class)
    {
        parent::__construct(
            response: $status,
            description: $description,
            content: new OA\JsonContent(
                properties: [new OA\Property(property: 'error', ref: new Model(type: $model))],
                required: ['error'],
            ),
        );
    }
}
