<?php

declare(strict_types=1);

namespace App\Tests\OpenApi;

use App\Http\ErrorCode;
use App\Tests\Support\DumpsOpenApiSpec;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The front's codegen reads error codes from the dumped spec, so this checks
 * the spec itself: every ErrorCode case is listed, every error body's
 * `code` points at that one schema instead of a plain string, and every
 * documented 4xx/5xx error response narrows `code` to exactly one known case
 * and carries it as `example` (which also catches an error documented with a
 * hand-written #[OA\Response] instead of #[ErrorResponse]).
 *
 * It can't detect a case no code path emits anymore (harmless for the
 * front: a documented code that never arrives), nor a narrowed code that
 * doesn't match what the endpoint returns: that's the endpoint's functional
 * test.
 */
final class ErrorCodeSchemaTest extends KernelTestCase
{
    use DumpsOpenApiSpec;

    private const string ERROR_CODE_REF = '#/components/schemas/ErrorCode';

    public function testListsEveryErrorCodeAsAStringEnum(): void
    {
        $spec = $this->dumpedSpec();

        self::assertSame('string', $this->at($spec, 'components', 'schemas', 'ErrorCode', 'type'));
        self::assertSame(
            array_map(static fn (ErrorCode $code): string => $code->value, ErrorCode::cases()),
            $this->at($spec, 'components', 'schemas', 'ErrorCode', 'enum'),
        );
    }

    public function testEveryErrorBodyReferencesTheErrorCodeSchema(): void
    {
        $spec = $this->dumpedSpec();

        self::assertSame(self::ERROR_CODE_REF, $this->at($spec, 'components', 'schemas', 'Error', 'properties', 'code', '$ref'));
        self::assertSame(self::ERROR_CODE_REF, $this->at($spec, 'components', 'schemas', 'ValidationError', 'properties', 'code', '$ref'));
    }

    /**
     * The shared `code` schema is the whole enum, which Swagger UI renders as
     * its first case: an error response that doesn't narrow it shows a wrong
     * code. Covers hand-written #[OA\Response] errors too, not only
     * #[ErrorResponse], whose required $code already enforces it.
     */
    public function testEveryDocumentedErrorResponseNarrowsItsCodeToOneErrorCode(): void
    {
        $spec = $this->dumpedSpec();
        $knownCodes = array_map(static fn (ErrorCode $code): string => $code->value, ErrorCode::cases());
        $checked = 0;

        foreach ($this->children($this->at($spec, 'paths')) as $path => $operations) {
            foreach ($this->children($operations) as $method => $operation) {
                foreach ($this->children($this->at($operation, 'responses')) as $status => $response) {
                    $error = $this->at($response, 'content', 'application/json', 'schema', 'properties', 'error');

                    if (null === $error || 1 !== preg_match('/^[45]\d\d$/', (string) $status)) {
                        continue;
                    }

                    $enum = $this->at($error, 'allOf', 1, 'properties', 'code', 'enum');
                    $where = sprintf('%s %s %s', strtoupper((string) $method), $path, $status);

                    self::assertIsArray($enum, $where.' does not narrow error.code.');
                    self::assertCount(1, $enum, $where.' should narrow error.code to exactly one value.');
                    self::assertContains($enum[0], $knownCodes, $where.' narrows error.code to an unknown code.');
                    self::assertSame(
                        $enum[0],
                        $this->at($error, 'allOf', 1, 'properties', 'code', 'example'),
                        $where.' should carry its code as example, or Swagger UI shows the first ErrorCode case.',
                    );
                    ++$checked;
                }
            }
        }

        self::assertGreaterThan(0, $checked, 'No documented error response found; the walk over the spec is broken.');
    }

    /**
     * Not annotated on the controllers: ValidationErrorResponseDescriber
     * infers it from #[MapRequestPayload]'s validationFailedStatusCode.
     */
    public function testInfersTheValidationErrorResponseOfMapRequestPayloadEndpoints(): void
    {
        $spec = $this->dumpedSpec();

        foreach (['/auth/register', '/auth/resend-confirmation'] as $path) {
            $error = $this->at($spec, 'paths', $path, 'post', 'responses', '422', 'content', 'application/json', 'schema', 'properties', 'error');

            self::assertSame('#/components/schemas/ValidationError', $this->at($error, 'allOf', 0, '$ref'), $path.' should document its 422 as ValidationError.');
            self::assertSame([ErrorCode::ValidationFailed->value], $this->at($error, 'allOf', 1, 'properties', 'code', 'enum'), $path);
        }
    }

    /**
     * confirm-email validates the query only through the link's signature,
     * so a bad query is its 400, never a validation error.
     */
    public function testConfirmEmailDocumentsNoValidationErrorResponse(): void
    {
        $responses = $this->at($this->dumpedSpec(), 'paths', '/auth/confirm-email', 'post', 'responses');

        self::assertIsArray($responses);
        self::assertSame(['200', '400'], array_map('strval', array_keys($responses)));
    }

    /**
     * @return array<array-key, mixed> $node's entries, or none if it isn't an array
     */
    private function children(mixed $node): array
    {
        return \is_array($node) ? $node : [];
    }
}
