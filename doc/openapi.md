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

## SpaceTraders (proxied) endpoints

All of these share a single physical route
(`/api/spacetraders/{path}`, see "Proxy design" in
[architecture.md](architecture.md)), so there's no one controller action per
logical endpoint to attach attributes to — hand-writing `#[OA\...]` per
endpoint isn't an option here the way it is for back-only ones.

Instead, a service implementing Nelmio's `DescriberInterface` (auto-tagged as
`nelmio_api_doc.describer`) builds the proxied part of the document
programmatically: for each SpaceTraders operation this back forwards, it
copies that operation's path/parameters/request/response schema from
SpaceTraders' own OpenAPI spec into this back's generated `OpenApi` object,
rewriting the path to live under `/api/spacetraders/...`. The schemas
themselves are never hand-transcribed — they come from SpaceTraders'
published spec, so the contract-fidelity rule in architecture.md ("the back
does not add, remove or rename fields") holds in the docs too, not just at
runtime.

## TODO

- Decide how the describer gets SpaceTraders' spec: vendor a copy of the JSON
  in this repo (stable, but goes stale silently until someone re-fetches it),
  or fetch it at build/cache-warmup time (always current, but adds a network
  dependency to doc generation). Not decided yet.
- Pick which SpaceTraders operations are in scope for the first pass (all of
  them vs. a representative subset) — this is partly an exercise, so starting
  with a handful and expanding is fine.
- Decide whether `data.token` (see the "Known simplification" / TODO in
  architecture.md) should be redacted in the documented response schema even
  before it's redacted at runtime.
