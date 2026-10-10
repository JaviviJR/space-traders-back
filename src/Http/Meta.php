<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Sibling of `data` in an envelope response, for listings — mirrors the
 * `meta.total/page/limit` shape SpaceTraders itself uses for its own
 * paginated listings.
 */
final readonly class Meta
{
    public function __construct(
        public int $total,
        public int $page,
        public int $limit,
    ) {
    }
}
