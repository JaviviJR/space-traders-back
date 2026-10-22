<?php

declare(strict_types=1);

namespace App\EventListener\ErrorMapper;

use App\Http\ErrorCode;
use App\Http\ErrorResponseData;
use App\Security\Exception\InvalidRefreshTokenException;
use App\Security\RefreshTokenCookieFactory;

/**
 * A refresh token that is missing, expired or revoked. Besides the 401, the
 * response clears the refresh token cookie: a token rejected once will be
 * rejected again, so there's no point in the browser keeping on sending it.
 *
 * No WWW-Authenticate, although RFC 9110 asks for one on a 401: the refresh
 * token travels in a cookie, so there is no HTTP authentication scheme to
 * challenge with, and Bearer would be false (this endpoint doesn't accept
 * an access token). Same reasoning as InvalidLoginCredentialsExceptionErrorMapper.
 */
final class InvalidRefreshTokenExceptionErrorMapper implements ExceptionErrorMapperInterface
{
    public function __construct(
        private readonly RefreshTokenCookieFactory $cookieFactory,
    ) {
    }

    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof InvalidRefreshTokenException;
    }

    public function map(\Throwable $exception): MappedError
    {
        return new MappedError(
            401,
            new ErrorResponseData(ErrorCode::InvalidRefreshToken, 'The refresh token is missing, expired or revoked.'),
            ['Set-Cookie' => (string) $this->cookieFactory->clear()],
        );
    }
}
