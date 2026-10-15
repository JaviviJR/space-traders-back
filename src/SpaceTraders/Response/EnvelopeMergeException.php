<?php

declare(strict_types=1);

namespace App\SpaceTraders\Response;

/**
 * Thrown by EnvelopeBuilder when extra fields can't be safely
 * merged into SpaceTraders' own response body. Both cases are bugs, not
 * runtime conditions to recover from — fail loud and early instead of
 * silently dropping data or letting one side win unpredictably.
 */
final class EnvelopeMergeException extends \LogicException
{
    /**
     * @param string[] $keys
     */
    public static function collision(array $keys): self
    {
        return new self(sprintf('Extra field(s) "%s" collide with the upstream response and cannot be merged.', implode(', ', $keys)));
    }

    public static function invalidUpstreamBody(): self
    {
        return new self('Cannot merge extra fields into an upstream body that is not a valid JSON object.');
    }
}
