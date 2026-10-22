<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Every value ErrorResponseData::$code can take: the stable, machine-readable
 * identifiers the front branches on and translates. Typing $code with this
 * enum makes the generated OpenAPI spec list every code the back can emit
 * (Nelmio describes backed enums natively), so the front's codegen gets them
 * as a TS union.
 *
 * Relayed SpaceTraders errors are not covered: their numeric `code` comes
 * from SpaceTraders' own catalog and never goes through ErrorResponseData.
 */
enum ApiErrorCode: string
{
    case AuthenticationRequired = 'authentication_required';
    case EmailAlreadyRegistered = 'email_already_registered';
    case EmailNotVerified = 'email_not_verified';
    case HttpError = 'http_error';
    case InternalError = 'internal_error';
    case InvalidCredentials = 'invalid_credentials';
    case InvalidEmailConfirmation = 'invalid_email_confirmation';
    case InvalidRefreshToken = 'invalid_refresh_token';
    case SpacetradersUnreachable = 'spacetraders_unreachable';
    case ValidationFailed = 'validation_failed';
}
