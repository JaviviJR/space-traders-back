<?php

declare(strict_types=1);

namespace App\Security\Dto;

use App\Http\ResponseData;

final readonly class ConfirmEmailResponseData implements ResponseData
{
    public function __construct(
        public bool $confirmed,
    ) {
    }
}
