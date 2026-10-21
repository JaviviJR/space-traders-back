<?php

declare(strict_types=1);

namespace App\OpenApi\Describer;

use App\Http\ErrorCode;
use App\OpenApi\Attribute\ErrorResponse;
use Nelmio\ApiDocBundle\Describer\DescriberInterface;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use OpenApi\Undefined;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Documents the 401 AuthenticationExceptionErrorMapper answers with on every
 * operation that requires the JWT, inferred from the operation's own
 * `security` so the two can't drift: Nelmio's SecurityDescriber sets it
 * from #[IsGranted] on back-only actions, ProxyDescriber on the proxied
 * ones.
 *
 * Priority -500 runs it after both of those and after RouteDescriber
 * (-400), which is what fills in the actions' hand-written responses: an
 * operation that already documents its 401 by hand is left alone.
 */
#[Autoconfigure(tags: [['name' => 'nelmio_api_doc.describer.default', 'priority' => -500]])]
final class AuthenticationErrorResponseDescriber implements DescriberInterface
{
    /**
     * The security scheme declared under the area's `security` in
     * config/packages/nelmio_api_doc.yaml.
     */
    public const string SECURITY_SCHEME = 'bearerAuth';

    public function describe(OA\OpenApi $api): void
    {
        if (Undefined::isDefault($api->paths)) {
            return;
        }

        foreach ($api->paths as $pathItem) {
            foreach (Util::OPERATIONS as $method) {
                $operation = $pathItem->{$method};

                if (Undefined::isDefault($operation) || !$this->requiresJwt($operation) || $this->documentsUnauthorized($operation)) {
                    continue;
                }

                $operation->merge([$this->unauthorizedResponse($operation)]);
            }
        }
    }

    private function requiresJwt(OA\Operation $operation): bool
    {
        if (Undefined::isDefault($operation->security)) {
            return false;
        }

        foreach ($operation->security as $requirement) {
            if (\is_array($requirement) && \array_key_exists(self::SECURITY_SCHEME, $requirement)) {
                return true;
            }
        }

        return false;
    }

    private function documentsUnauthorized(OA\Operation $operation): bool
    {
        if (Undefined::isDefault($operation->responses)) {
            return false;
        }

        foreach ($operation->responses as $response) {
            if ('401' === (string) $response->response) {
                return true;
            }
        }

        return false;
    }

    /**
     * Built under the operation's context, as ValidationErrorResponseDescriber
     * does: otherwise the dump rejects the response's JsonContent as "must
     * be nested".
     */
    private function unauthorizedResponse(OA\Operation $operation): ErrorResponse
    {
        $previousContext = Generator::$context;
        Generator::$context = Util::createContext(['nested' => $operation], $operation->_context);

        try {
            return new ErrorResponse(
                status: 401,
                code: ErrorCode::AuthenticationRequired,
                description: 'Missing or invalid access token',
            );
        } finally {
            Generator::$context = $previousContext;
        }
    }
}
