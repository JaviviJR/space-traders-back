<?php

declare(strict_types=1);

namespace App\Tests\OpenApi;

use App\Http\ErrorCode;
use App\OpenApi\Describer\AuthenticationErrorResponseDescriber;
use App\Tests\Support\DumpsOpenApiSpec;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The 401 of a rejected JWT isn't annotated on any controller:
 * AuthenticationErrorResponseDescriber infers it from each operation's
 * `security`. This checks the dumped spec so a protected operation can't
 * end up without it.
 */
final class AuthenticationErrorResponseTest extends KernelTestCase
{
    use DumpsOpenApiSpec;

    public function testEveryOperationRequiringTheJwtDocumentsTheAuthenticationRequiredError(): void
    {
        $checked = 0;

        foreach ($this->operations($this->dumpedSpec()) as $where => $operation) {
            if (!$this->requiresJwt($operation)) {
                continue;
            }

            $error = $this->at($operation, 'responses', '401', 'content', 'application/json', 'schema', 'properties', 'error');

            self::assertSame('#/components/schemas/Error', $this->at($error, 'allOf', 0, '$ref'), $where.' should document its 401 as Error.');
            self::assertSame([ErrorCode::AuthenticationRequired->value], $this->at($error, 'allOf', 1, 'properties', 'code', 'enum'), $where);
            ++$checked;
        }

        self::assertGreaterThan(0, $checked, 'No operation requires the JWT; the walk over the spec is broken.');
    }

    /**
     * SpaceTraders' own security requirements describe the upstream hop;
     * the front authenticates every proxied call with this back's JWT.
     */
    public function testEveryProxiedOperationRequiresTheJwt(): void
    {
        $checked = 0;

        foreach ($this->operations($this->dumpedSpec()) as $where => $operation) {
            if (!str_contains($where, ' /api/spacetraders')) {
                continue;
            }

            self::assertSame([[AuthenticationErrorResponseDescriber::SECURITY_SCHEME => []]], $this->at($operation, 'security'), $where);
            ++$checked;
        }

        self::assertGreaterThan(0, $checked, 'No proxied operation found; the walk over the spec is broken.');
    }

    public function testPublicOperationsDoNotDocumentTheAuthenticationRequiredError(): void
    {
        $operations = $this->operations($this->dumpedSpec());

        self::assertArrayHasKey('POST /auth/register', $operations);
        self::assertNull($this->at($operations['POST /auth/register'], 'responses', '401'));
    }

    private function requiresJwt(mixed $operation): bool
    {
        $security = $this->at($operation, 'security');

        if (!\is_array($security)) {
            return false;
        }

        foreach ($security as $requirement) {
            if (\is_array($requirement) && \array_key_exists(AuthenticationErrorResponseDescriber::SECURITY_SCHEME, $requirement)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed> every operation, keyed by "METHOD /path"
     */
    private function operations(mixed $spec): array
    {
        $operations = [];
        $paths = $this->at($spec, 'paths');

        foreach (\is_array($paths) ? $paths : [] as $path => $pathItem) {
            foreach (\is_array($pathItem) ? $pathItem : [] as $method => $operation) {
                if (\is_array($operation) && \array_key_exists('responses', $operation)) {
                    $operations[strtoupper((string) $method).' '.$path] = $operation;
                }
            }
        }

        return $operations;
    }
}
