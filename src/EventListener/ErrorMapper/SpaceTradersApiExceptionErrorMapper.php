<?php

declare(strict_types=1);

namespace App\EventListener\ErrorMapper;

use App\Http\ErrorCode;
use App\Http\ErrorResponseData;
use App\SpaceTraders\Exception\ApiException;

/**
 * The gateway's genuine transport failures (timeout, DNS, connection) — not
 * a relayed SpaceTraders 4xx/5xx. The back acted as a proxy and the
 * upstream didn't respond, so this is a 502, not a 500: that's reserved for
 * the back's own internal failures.
 */
final class SpaceTradersApiExceptionErrorMapper implements ExceptionErrorMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof ApiException;
    }

    public function map(\Throwable $exception): MappedError
    {
        return new MappedError(502, new ErrorResponseData(ErrorCode::SpacetradersUnreachable, 'SpaceTraders is currently unreachable.'));
    }
}
