<?php

declare(strict_types=1);

namespace App\OpenApi\Attribute;

use App\Http\ApiErrorCode;
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
 * $code is required: the shared schema's `code` is the whole ApiErrorCode enum,
 * which Swagger UI would render as its first case, a wrong example on almost
 * every endpoint. Narrowing it here (allOf the model plus a single-value
 * enum) gives the front's codegen the exact literal. The narrowed code also
 * carries it as `example`: Swagger UI's allOf merge keeps the shared enum
 * (it doesn't override enums by design, swagger-ui#3674), so without it the
 * example would still show the first case.
 *
 * Checked by running the Swagger UI bundled with Nelmio (its resolver and
 * sample generator) on the dumped spec: derived from that vendor/ code, not
 * from Swagger UI's docs.
 *
 * Don't annotate errors that are inferred: the validation error of a
 * #[MapRequestPayload] / #[MapQueryString] argument
 * (ValidationErrorResponseDescriber) and the 401 authentication_required of
 * any action requiring the JWT (AuthenticationErrorResponseDescriber).
 * Annotate only what follows from the action's logic, i.e. the exceptions its
 * service throws.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class ErrorResponse extends OA\Response
{
    /**
     * @param class-string<ErrorResponseData> $model
     */
    public function __construct(int $status, ApiErrorCode $code, string $description = '', string $model = ErrorResponseData::class)
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
                                properties: [new OA\Property(property: 'code', type: 'string', enum: [$code->value], example: $code->value)],
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
