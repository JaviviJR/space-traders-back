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

Login, register, refresh, confirm-email, resend-confirmation and logout all
carry `#[OA\Tag(name: 'Account')]`, same as `GET /api/agents` — no separate
`Auth` tag was introduced.

`App\OpenApi\Attribute\DataResponse` and `App\OpenApi\Attribute\ErrorResponse`
mark their `data`/`error` property `required` on the generated `JsonContent`:
a 2xx without `data` or a non-2xx without `error` is a contract violation, not
a case the front needs to guard against with `?.`.

`ErrorResponse` documents the `error` property as `ErrorResponseData` by
default. When an endpoint can return an error whose body is a subclass with
extra fields (see "Adding a mapper" in [errors.md](errors.md)), pass it as
`model:` on that status's `#[ErrorResponse]` so the schema shows what is
actually sent; the other statuses keep the default.


## SpaceTraders (proxied) endpoints

All of these share a single physical route
(`/api/spacetraders/{path}`, see "Proxy design" in
[spacetraders-proxy.md](spacetraders-proxy.md)), so there's no one controller action per
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
the contract-fidelity rule in [spacetraders-proxy.md](spacetraders-proxy.md) ("the back does not add, remove
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
"Proxy design" in [spacetraders-proxy.md](spacetraders-proxy.md)) has a doc-time counterpart to `augment()`:
`describedAugmentation(): App\SpaceTraders\Response\AugmentationSchema`, returning which
operation (method + path) the hook adds fields to and the `ResponseData`
class those fields come from (the same marker back-only responses use —
see "Proxy design" in [spacetraders-proxy.md](spacetraders-proxy.md)).
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

## Schema names: keep `Dto`/`*ResponseData` out of the front

Back-only request/response classes follow internal PHP naming conventions —
`Dto` suffix for request payloads, `*ResponseData` for the
`App\Http\ResponseData` envelope marker (see "Proxy design" in
[spacetraders-proxy.md](spacetraders-proxy.md)). Those suffixes are meaningful here, but they must not leak into the
generated spec: the front's codegen turns each component schema name
verbatim into a TS type name, so `AgentResponseData` would show up front-side
as `AgentResponseData` instead of a clean `Agent`-like name.

**Every new back-only request DTO or response data class must get a clean
alias in `config/packages/nelmio_api_doc.yaml`'s `models.names`**, named after
what the class contains, not after its PHP role:

```yaml
models:
    names:
        - { alias: ConfirmEmail, type: App\Security\Dto\ConfirmEmailDto }
```

This has to be the YAML alias list, not a per-controller
`new Model(type: ..., name: '...')` on the `#[OA\RequestBody]` /
`App\OpenApi\Attribute\DataResponse` attribute, for any class used behind
`#[MapRequestPayload]`: Nelmio's `SymfonyMapRequestPayloadDescriber` always
registers that class a second time under its bare class name, independently
of whatever name the controller's own attribute uses, leaving an orphan
unreferenced schema (e.g. `ConfirmEmailDto` with no `$ref` to it) alongside
the clean one. The YAML alias is checked before any attribute-level name, so
it's the only place that converges both registrations onto one schema. Response-only
classes (no `#[MapRequestPayload]` involved) don't strictly need the YAML
route, but everything stays in `models.names` anyway so there's one mechanism
to look at, not two.

Pick a name that doesn't collide with an existing schema — in particular
anything SpaceTraders' own vendored spec already defines (injected by
`App\SpaceTraders\OpenApi\ProxyDescriber`, see above): `AgentResponseData`
couldn't alias to `Agent` because SpaceTraders already has one, hence
`AccountAgent`. Nelmio silently suffixes a colliding name (`Agent2`) instead
of erroring, so check the dumped spec
(`bin/console nelmio:apidoc:dump --format=json`) after adding an alias to
confirm it landed as intended.

## TODO

- No command re-fetches/re-bundles `resources/spacetraders/openapi.json` yet;
  it's a manual re-export from SpaceTraders' spec when it changes.
- Expand `App\SpaceTraders\OpenApi\ProxyDescriber::OPERATIONS` beyond the representative
  subset above.
