<?php

declare(strict_types=1);

namespace App\OpenApi\RouteArgumentDescriber;

use App\Http\ApiErrorCode;
use App\Http\ValidationErrorResponseData;
use App\OpenApi\Attribute\ErrorResponse;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\RouteDescriber\RouteArgumentDescriber\RouteArgumentDescriberInterface;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use OpenApi\Undefined;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Documents the validation error #[MapRequestPayload] / #[MapQueryString]
 * respond with, at the status the attribute itself sets
 * (validationFailedStatusCode: 422 and 404 by default), so the spec can't
 * drift from what the argument resolver actually returns. The body is what
 * ValidationFailedExceptionErrorMapper builds from that failure.
 *
 * Leaves an operation alone if it already documents that status by hand:
 * Nelmio reads the controller's own attributes before running route
 * argument describers, so those win.
 */
final class ValidationErrorResponseDescriber implements RouteArgumentDescriberInterface
{
    public function describe(ArgumentMetadata $argumentMetadata, OA\Operation $operation): void
    {
        $attribute = $argumentMetadata->getAttributes(MapRequestPayload::class, ArgumentMetadata::IS_INSTANCEOF)[0]
            ?? $argumentMetadata->getAttributes(MapQueryString::class, ArgumentMetadata::IS_INSTANCEOF)[0]
            ?? null;

        if (!$attribute instanceof MapRequestPayload && !$attribute instanceof MapQueryString) {
            return;
        }

        $status = $attribute->validationFailedStatusCode;

        if ($this->documentsStatus($operation, $status)) {
            return;
        }

        $operation->merge([$this->validationErrorResponse($operation, $status)]);
    }

    /**
     * swagger-php only marks the annotations a constructor receives as
     * nested when they share Generator::$context with their parent, the way
     * its attribute factory builds them; built without it, the dump rejects
     * the response's JsonContent as "must be nested".
     */
    private function validationErrorResponse(OA\Operation $operation, int $status): ErrorResponse
    {
        $previousContext = Generator::$context;
        Generator::$context = Util::createContext(['nested' => $operation], $operation->_context);

        try {
            return new ErrorResponse(
                status: $status,
                code: ApiErrorCode::ValidationFailed,
                description: 'Validation failed',
                model: ValidationErrorResponseData::class,
            );
        } finally {
            Generator::$context = $previousContext;
        }
    }

    private function documentsStatus(OA\Operation $operation, int $status): bool
    {
        if (Undefined::isDefault($operation->responses)) {
            return false;
        }

        foreach ($operation->responses as $response) {
            if ((string) $response->response === (string) $status) {
                return true;
            }
        }

        return false;
    }
}
