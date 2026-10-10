<?php

declare(strict_types=1);

namespace App\Security\Dto;

use App\Http\ResponseData;

/**
 * No fields on purpose: ResendConfirmationController always answers 202 with
 * `data: {}` regardless of whether the email existed, so it never leaks
 * which emails are registered.
 */
final readonly class EmptyResponseData implements ResponseData
{
}
