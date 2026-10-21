<?php

declare(strict_types=1);

namespace App\Tests\OpenApi\RouteArgumentDescriber;

use App\OpenApi\Attribute\ErrorResponse;
use App\OpenApi\RouteArgumentDescriber\ValidationErrorResponseDescriber;
use OpenApi\Annotations as OA;
use OpenApi\Attributes as OAT;
use OpenApi\Undefined;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class ValidationErrorResponseDescriberTest extends TestCase
{
    public function testDocumentsMapQueryStringAtItsDefaultStatus(): void
    {
        $operation = new OAT\Post();

        (new ValidationErrorResponseDescriber())->describe($this->argument(new MapQueryString()), $operation);

        self::assertSame([404], $this->documentedStatuses($operation));
    }

    public function testDocumentsTheValidationFailedStatusCodeTheAttributeSets(): void
    {
        $operation = new OAT\Post();

        (new ValidationErrorResponseDescriber())->describe($this->argument(new MapRequestPayload(validationFailedStatusCode: 400)), $operation);

        self::assertSame([400], $this->documentedStatuses($operation));
    }

    public function testLeavesAStatusAlreadyDocumentedByHandAlone(): void
    {
        $operation = new OAT\Post(responses: [new OAT\Response(response: 422, description: 'Hand-written')]);

        (new ValidationErrorResponseDescriber())->describe($this->argument(new MapRequestPayload()), $operation);

        self::assertSame([422], $this->documentedStatuses($operation));
        self::assertNotInstanceOf(ErrorResponse::class, $operation->responses[0]);
        self::assertSame('Hand-written', $operation->responses[0]->description);
    }

    public function testIgnoresAnArgumentWithoutAPayloadAttribute(): void
    {
        $operation = new OAT\Post();

        (new ValidationErrorResponseDescriber())->describe($this->argument(), $operation);

        self::assertTrue(Undefined::isDefault($operation->responses));
    }

    private function argument(object ...$attributes): ArgumentMetadata
    {
        return new ArgumentMetadata('dto', \stdClass::class, false, false, null, attributes: $attributes);
    }

    /**
     * @return list<int|string>
     */
    private function documentedStatuses(OA\Operation $operation): array
    {
        return array_map(static fn (OA\Response $response): int|string => $response->response, $operation->responses);
    }
}
