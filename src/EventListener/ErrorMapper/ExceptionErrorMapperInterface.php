<?php

declare(strict_types=1);

namespace App\EventListener\ErrorMapper;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * One class per kind of exception that needs its own (status, code,
 * message) story. ExceptionListener stays a thin kernel.exception hook: it
 * just finds the first mapper whose supports() agrees and uses its map().
 * Add a new exception case by adding a new implementation, not by editing
 * the listener.
 */
#[AutoconfigureTag('app.exception_error_mapper')]
interface ExceptionErrorMapperInterface
{
    public function supports(\Throwable $exception): bool;

    public function getCode(): string;

    public function map(\Throwable $exception): MappedError;
}
