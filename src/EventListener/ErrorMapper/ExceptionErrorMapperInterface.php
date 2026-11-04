<?php

declare(strict_types=1);

namespace App\EventListener\ErrorMapper;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * One class per kind of exception that needs its own (status, code,
 * message) story. ExceptionResponseFactory finds the first mapper whose
 * supports() agrees and uses its map(); the entry points that call it stay
 * thin. Add a new exception case by adding a new implementation, not by
 * editing the factory.
 *
 * Adding a mapper:
 *
 * - Use a fixed message by default. Reuse getMessage() only when the
 *   exception's message is written for the front (as HttpExceptionInterface
 *   messages are); a domain exception's message is not a public contract.
 * - Extra body fields go in a readonly subclass of ErrorResponseData in
 *   src/Http/, never a nullable field on ErrorResponseData. Document it with
 *   #[ErrorResponse(..., model: TheSubclass::class)].
 * - Unit-test the mapper in tests/EventListener/ErrorMapper/ and assert the
 *   code in the functional test of the endpoint that triggers it.
 *
 * Never match a framework class that can arrive from several origins (e.g.
 * AuthenticationException): the entry point that knows the origin wraps it in
 * one of our own exceptions first. Anything unmapped becomes a 500
 * internal_error, which is reserved for the back's own failures.
 */
#[AutoconfigureTag('app.exception_error_mapper')]
interface ExceptionErrorMapperInterface
{
    public function supports(\Throwable $exception): bool;

    public function map(\Throwable $exception): MappedError;
}
