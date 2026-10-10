<?php

declare(strict_types=1);

namespace App\Security\Dto;

use App\Http\ResponseData;

/**
 * Shared by LoginController and RefreshController: both hand back exactly
 * this shape.
 */
final readonly class AccessTokenResponseData implements ResponseData
{
    public function __construct(
        public string $accessToken,
    ) {
    }
}
