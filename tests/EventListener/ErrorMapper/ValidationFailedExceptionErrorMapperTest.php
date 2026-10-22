<?php

declare(strict_types=1);

namespace App\Tests\EventListener\ErrorMapper;

use App\EventListener\ErrorMapper\ValidationFailedExceptionErrorMapper;
use App\Http\ApiErrorCode;
use App\Http\ValidationErrorResponseData;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ValidationFailedExceptionErrorMapperTest extends TestCase
{
    public function testSupportsAnHttpExceptionWrappingAValidationFailedException(): void
    {
        $mapper = new ValidationFailedExceptionErrorMapper();
        $exception = new HttpException(422, 'irrelevant', new ValidationFailedException(null, new ConstraintViolationList()));

        self::assertTrue($mapper->supports($exception));
    }

    public function testDoesNotSupportAPlainHttpException(): void
    {
        $mapper = new ValidationFailedExceptionErrorMapper();

        self::assertFalse($mapper->supports(new NotFoundHttpException()));
    }

    public function testGroupsEveryViolationMessageUnderItsField(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('This value is too short. It should have 8 characters or more.', null, [], null, 'password', 'short'),
            new ConstraintViolation('This value is not a valid email address.', null, [], null, 'email', 'not-an-email'),
            new ConstraintViolation('This value is not valid.', null, [], null, 'password', 'short'),
        ]);
        $exception = new HttpException(422, 'irrelevant', new ValidationFailedException(null, $violations));

        $mapped = (new ValidationFailedExceptionErrorMapper())->map($exception);

        self::assertSame(422, $mapped->status);
        self::assertSame(ApiErrorCode::ValidationFailed, $mapped->body->code);
        self::assertSame('Validation failed.', $mapped->body->message);
        self::assertInstanceOf(ValidationErrorResponseData::class, $mapped->body);
        self::assertSame(
            [
                'password' => ['This value is too short. It should have 8 characters or more.', 'This value is not valid.'],
                'email' => ['This value is not a valid email address.'],
            ],
            $mapped->body->violations,
        );
    }

    public function testPutsViolationsWithoutAPropertyPathUnderTheGlobalKey(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('The passwords do not match.', null, [], null, '', null),
        ]);
        $exception = new HttpException(422, 'irrelevant', new ValidationFailedException(null, $violations));

        $mapped = (new ValidationFailedExceptionErrorMapper())->map($exception);

        self::assertInstanceOf(ValidationErrorResponseData::class, $mapped->body);
        self::assertSame(['_global' => ['The passwords do not match.']], $mapped->body->violations);
    }
}
