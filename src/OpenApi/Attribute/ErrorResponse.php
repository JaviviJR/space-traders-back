<?php

declare(strict_types=1);

namespace App\OpenApi\Attribute;

use App\Http\ErrorCode;
use App\Http\ErrorResponseData;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

/**
 * `#[OA\Response]` for the `error` side of EnvelopeResponseFactory::error():
 * every back-only error response shares the same ErrorResponseData shape,
 * so this only needs a status, the code it returns and a description, not a
 * model per call site. An error kind whose body extends ErrorResponseData
 * with extra fields passes that subclass as $model so its schema shows them.
 *
 * $code is required: the shared schema's `code` is the whole ErrorCode enum,
 * which Swagger UI would render as its first case, a wrong example on almost
 * every endpoint. Narrowing it here (allOf the model plus a single-value
 * enum) shows the real code and gives the front's codegen the exact literal.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class ErrorResponse extends OA\Response
{
    /**
     * @param class-string<ErrorResponseData> $model
     */
    public function __construct(int $status, ErrorCode $code, string $description = '', string $model = ErrorResponseData::class)
    {
        parent::__construct(
            response: $status,
            description: $description,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'error',
                        allOf: [
                            new OA\Schema(ref: new Model(type: $model)),
                            new OA\Schema(
                                properties: [new OA\Property(property: 'code', type: 'string', enum: [$code->value])],
                                required: ['code'],
                            ),
                        ],
                    ),
                ],
                required: ['error'],
            ),
        );
    }
}
