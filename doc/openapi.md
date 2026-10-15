# OpenAPI documentation

## Decision

This back exposes an OpenAPI spec for the front, similar to what SpaceTraders
publishes at https://spacetraders.io/api-guide/open-api-spec. No API Platform
(see [workflow.md](workflow.md) working agreements / prior decision): plain
Symfony controllers stay as they are, and the spec is generated on top of them
with **NelmioApiDocBundle** (`zircote/swagger-php` provides the `#[OA\...]`
attributes it reads).

This also doubles as a learning exercise, so the two endpoint kinds described
in [architecture.md](architecture.md) are documented with two different
techniques on purpose.

## Back-only endpoints

Registration, login, token refresh, logout, `GET /api/agents`: each has its
own real route and its own contract, so they're documented the normal way —
`#[OA\...]` attributes directly on the controller action and on the
request/response DTOs.

They're grouped into Nelmio tags by domain, not by the fact that they're
back-only (that distinction already lives in this doc's section titles, not
in the generated spec). `GET /api/agents` carries `#[OA\Tag(name: 'Account')]`.

**TODO:** when login/register/refresh/logout get their own `#[OA\...]`
attributes, decide whether they also belong under `Account` or warrant a
separate domain tag (e.g. `Auth`) — not resolved yet, revisit then.


## SpaceTraders (proxied) endpoints

All of these share a single physical route
(`/api/spacetraders/{path}`, see "Proxy design" in
[architecture.md](architecture.md)), so there's no one controller action per
logical endpoint to attach attributes to — hand-writing `#[OA\...]` per
endpoint isn't an option here the way it is for back-only ones.

Instead, `App\SpaceTraders\OpenApi\ProxyDescriber` implements
Nelmio's `DescriberInterface` — **not** auto-tagged by the bundle (unlike
`ModelDescriberInterface`/`TypeDescriberInterface`/
`RouteArgumentDescriberInterface`), so it carries its own
`#[Autoconfigure(tags: ['nelmio_api_doc.describer.default'])]` (`default` is
the only area this app configures). For each SpaceTraders operation this back
forwards, it copies that operation's path/parameters/request/response schema
— and every component schema it transitively `$ref`s — from SpaceTraders'
own OpenAPI spec into this back's generated `OpenApi` object, rewriting the
path to live under `/api/spacetraders/...`. The schemas themselves are never
hand-transcribed — they come straight from SpaceTraders' published spec
(deserialized via `zircote/swagger-php`'s own `OpenApi\Serializer`, into the
same annotation objects the classic `#[OA\...]` attributes would produce), so
the contract-fidelity rule in architecture.md ("the back does not add, remove
or rename fields") holds in the docs too, not just at runtime.

**Spec source:** vendored at `resources/spacetraders/openapi.json` — a
bundled (all `$ref`s resolved to `#/components/schemas/...`) export of
SpaceTraders' published spec, not the raw multi-file source `api-docs`
repository publishes. Stable and offline, at the cost of going stale silently
until someone re-fetches it; re-vendoring is a manual, occasional step (no
command for it yet — see TODO).

**Hiding the physical catch-all route:** `App\Controller\SpaceTraders\SpaceTradersProxyController`'s own
route (`/api/spacetraders/{path}`) must not also show up in the generated
document via Nelmio's normal attribute reflection — it would be redundant
with, and confusing next to, the synthetic operations
`App\SpaceTraders\OpenApi\ProxyDescriber` already injects. Excluded via
`config/packages/nelmio_api_doc.yaml`'s `areas.path_patterns`
(`^/api(?!/doc$)(?!/spacetraders)`), which drops the whole
`/api/spacetraders/*` family from reflection-based documentation. Two other
mechanisms were considered and rejected: `#[OA\Hidden]` doesn't exist in the
installed `zircote/swagger-php` (6.12.0); and Nelmio's `#[Areas]` +
`with_attribute: true` would work but is built for splitting a document into
several rendered areas, more machinery than needed to hide a single
controller's route.

**Operations in scope:** a representative subset for this first pass
(`App\SpaceTraders\OpenApi\ProxyDescriber::OPERATIONS`): server status (`GET /`), agent
registration (`POST /register`) and listing ships (`GET /my/ships`). Picked
to exercise both plain pass-through operations and one with a hook
augmentation. Expand the list to document more.

**Hook augmentations:** `App\SpaceTraders\Response\AugmentInterface` (see
"Proxy design" in architecture.md) has a doc-time counterpart to `augment()`:
`describedAugmentation(): App\SpaceTraders\Response\AugmentationSchema`, returning which
operation (method + path) the hook adds fields to and the `ResponseData`
class those fields come from (the same marker back-only responses use —
see "Proxy design" in architecture.md).
`App\SpaceTraders\OpenApi\ProxyDescriber` asks every hook for it and merges the class's
public properties into the matching operation's response schema as siblings
of `data` — no edit to the describer needed when a new augmenting hook is
added (Open/Closed: extension by implementing the method on the new hook, not
by editing a central list). Deliberately separate from `supports()`, which
needs a real `App\SpaceTraders\Proxy\ProxyRequest` (and a real `User`) to decide — doc
generation has neither, so this duplicates the path/method match in a small,
hook-local way rather than fabricating a request just to ask.

**`data.token`:** documented as it is actually relayed today (plaintext, see
the "Known simplification" / TODO in architecture.md), not redacted ahead of
the runtime fix — the docs must reflect the real contract, not the desired
one. Update the schema in the same change that redacts it at runtime.

## TODO

- No command re-fetches/re-bundles `resources/spacetraders/openapi.json` yet;
  it's a manual re-export from SpaceTraders' spec when it changes.
- Expand `App\SpaceTraders\OpenApi\ProxyDescriber::OPERATIONS` beyond the representative
  subset above.
