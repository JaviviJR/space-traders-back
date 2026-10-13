<?php

declare(strict_types=1);

namespace App\Http;

/**
 * The content of an envelope response, before it becomes a JSON string:
 * $upstream is another system's own raw, undecoded JSON body (null when
 * there is none, e.g. a back-only response); $extra is top-level fields our
 * own code adds on top.
 *
 * Without $extra, $upstream passes through untouched — no decode/re-encode.
 * With $extra and no $upstream, $extra alone is the whole body. With both,
 * $upstream is decoded just enough to merge $extra in as sibling fields,
 * never overriding a key $upstream already has — that would be a bug in the
 * caller, so it throws rather than silently picking a winner.
 */
final readonly class EnvelopeBody
{
    /**
     * @param array<string, mixed> $extra
     */
    public function __construct(
        public ?string $upstream,
        public array $extra = [],
    ) {
    }

    public function toJson(): string
    {
        if (null === $this->upstream) {
            return json_encode($this->extra, \JSON_THROW_ON_ERROR);
        }

        if ([] === $this->extra) {
            return $this->upstream;
        }

        $decoded = json_decode($this->upstream, true);

        if (!\is_array($decoded)) {
            throw EnvelopeMergeException::invalidUpstreamBody();
        }

        $collisions = array_keys(array_intersect_key($decoded, $this->extra));

        if ([] !== $collisions) {
            throw EnvelopeMergeException::collision($collisions);
        }

        return json_encode($decoded + $this->extra, \JSON_THROW_ON_ERROR);
    }
}
