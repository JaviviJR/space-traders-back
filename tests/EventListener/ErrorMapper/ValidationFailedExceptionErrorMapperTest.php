<?php

declare(strict_types=1);

namespace App\Tests\EventListener\ErrorMapper;

use App\EventListener\ErrorMapper\ValidationFailedExceptionErrorMapper;
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

    public function testMapsEachViolationToItsFieldAndProblem(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('This value is too short. It should have 8 characters or more.', null, [], null, 'password', 'short'),
            new ConstraintViolation('This value is not a valid email address.', null, [], null, 'email', 'not-an-email'),
        ]);
        $exception = new HttpException(422, 'irrelevant', new ValidationFailedException(null, $violations));

        $mapped = (new ValidationFailedExceptionErrorMapper())->map($exception);

        self::assertSame(422, $mapped->status);
        self::assertSame('validation_failed', $mapped->code);
        self::assertSame(
            'password: This value is too short. It should have 8 characters or more.; '
            .'email: This value is not a valid email address.',
            $mapped->message,
        );
    }
}
