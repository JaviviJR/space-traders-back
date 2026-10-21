<?php

declare(strict_types=1);

namespace App\EventListener\ErrorMapper;

use App\Security\Exception\EmailAlreadyRegisteredException;

/**
 * Registration with an email that already has an account. A 409 with its
 * own code so the front can branch on it (e.g. offer to log in instead)
 * without parsing the message, which is fixed here rather than taken from
 * the exception: the domain message isn't written as a public contract.
 */
final class EmailAlreadyRegisteredExceptionErrorMapper implements ExceptionErrorMapperInterface
{
    private const string CODE = 'email_already_registered';

    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof EmailAlreadyRegisteredException;
    }

    public function map(\Throwable $exception): MappedError
    {
        return new MappedError(409, self::CODE, 'This email is already registered.');
    }
}
