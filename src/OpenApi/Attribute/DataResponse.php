<?php

declare(strict_types=1);

namespace App\OpenApi\Attribute;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

/**
 * `#[OA\Response]` for the `data` side of EnvelopeResponseFactory::success():
 * documents the `{"data": ...}` wrapper that every back-only success
 * response shares, so controllers declare a model and a status instead of
 * repeating the JsonContent/Property/Model wiring per action.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class DataResponse extends OA\Response
{
    /**
     * @param class-string $model
     */
    public function __construct(string $model, int $status = 200, string $description = '', bool $list = false)
    {
        $dataProperty = $list
            ? new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: $model)))
            : new OA\Property(property: 'data', ref: new Model(type: $model));

        parent::__construct(
            response: $status,
            description: $description,
            content: new OA\JsonContent(properties: [$dataProperty], required: ['data']),
        );
    }
}
