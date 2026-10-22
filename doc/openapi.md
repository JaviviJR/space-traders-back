# OpenAPI documentation

## Decision

This back exposes an OpenAPI spec for the front, similar to what SpaceTraders
publishes at https://spacetraders.io/api-guide/open-api-spec. No API
Platform: plain Symfony controllers stay as they are, and the spec is
generated on top of them with **NelmioApiDocBundle** (`zircote/swagger-php`
provides the `#[OA\...]` attributes it reads).

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
in the generated spec). Authentication endpoints belong to the `Account`
domain like `GET /api/agents`; there's no separate `Auth` tag.

`App\OpenApi\Attribute\DataResponse` and `App\OpenApi\Attribute\ErrorResponse`
mark their `data`/`error` property `required` on the generated `JsonContent`:
a 2xx without `data` or a non-2xx without `error` is a contract violation, not
a case the front needs to guard against with `?.`.

`ErrorResponse` documents the `error` property as `ErrorResponseData` by
default. When an endpoint can return an error whose body is a subclass with
extra fields (see "Adding a mapper" in [errors.md](errors.md)), pass it as
`model:` on that status's `#[ErrorResponse]` so the schema shows what is
actually sent; the other statuses keep the default.

A `ResponseData` subclass declares its parent on the class with
`#[OA\Schema(allOf: [new OA\Schema(ref: new Model(type: Parent::class))])]`,
so the spec (and the front's codegen) shows it extends the parent's schema
instead of an unrelated one. Accepted cost: the inherited properties are
still repeated inside that `allOf`. Nelmio reads every property of a model,
inherited ones included, even with a class-level `#[OA\Schema]` ("a
`type="object"` will still read all a models properties", Nelmio's
[FAQ](https://symfony.com/bundles/NelmioApiDocBundle/current/faq.html)), and
swagger-php's `Inheritance` augmenter, which would drop them, doesn't run
under Nelmio ([modes](https://zircote.github.io/swagger-php/guide/modes.html)).
A custom model describer could emit the `allOf` without the repetition;
revisit it if subclasses multiply or the front's codegen trips on the
duplicated properties.

`ErrorResponse` also takes a required `code:` (an `App\Http\ApiErrorCode`
case): the code that status returns on that endpoint. See "Per-response
code" under "Error codes" below for why it's required.


## SpaceTraders (proxied) endpoints

All of these share a single physical route
(`/api/spacetraders/{path}`, see "Proxy design" in
[spacetraders-proxy.md](spacetraders-proxy.md)), so there's no one controller action per
logical endpoint to attach attributes to — hand-writing `#[OA\...]` per
endpoint isn't an option here the way it is for back-only ones.

Instead, `App\SpaceTraders\OpenApi\ProxyDescriber` implements Nelmio's
`DescriberInterface`. For each SpaceTraders operation this back forwards, it
copies that operation's path/parameters/request/response schema — and every
component schema it transitively `$ref`s — from SpaceTraders' own OpenAPI
spec into this back's generated `OpenApi` object, rewriting the path to live
under `/api/spacetraders/...`. The schemas themselves are never
hand-transcribed, so the contract-fidelity rule in
[spacetraders-proxy.md](spacetraders-proxy.md) ("the back does not add,
remove or rename fields") holds in the docs too, not just at runtime.

Nelmio doesn't auto-tag a `DescriberInterface` (unlike
`ModelDescriberInterface`/`TypeDescriberInterface`/
`RouteArgumentDescriberInterface`): every describer of our own carries
`#[Autoconfigure(tags: ['nelmio_api_doc.describer.default'])]` (`default` is
the only area this app configures).

**Security:** the copied operation's `security` is replaced with this
back's `bearerAuth`. The front authenticates every proxied call with the
back's JWT; SpaceTraders' own requirements describe the upstream hop. This
doesn't break the contract-fidelity rule, which covers request/response
fields, not authentication. It's also what gets these operations their
inferred `401` (see "Authentication errors are inferred" under "Error
codes").

**Spec source:** vendored at `resources/spacetraders/openapi.json` — a
bundled (all `$ref`s resolved to `#/components/schemas/...`) export of
SpaceTraders' published spec, not the raw multi-file source `api-docs`
repository publishes. Stable and offline, at the cost of going stale silently
until someone re-fetches it; re-vendoring is a manual, occasional step (no
command for it yet — see TODO).

**The physical catch-all route is not documented:** `/api/spacetraders/*` is
excluded from Nelmio's reflection-based documentation through
`areas.path_patterns` in `config/packages/nelmio_api_doc.yaml`, so the
controller's own route doesn't show up next to the synthetic operations
`ProxyDescriber` injects.

**Operations in scope:** only the operations listed in
`ProxyDescriber::OPERATIONS` are documented; add one there to document it.

**Hook augmentations:** a hook implementing
`App\SpaceTraders\Response\AugmentInterface` (see "Proxy design" in
[spacetraders-proxy.md](spacetraders-proxy.md)) documents the fields it adds
through `describedAugmentation()`, its doc-time counterpart to `augment()`:
the operation (method + path) and the `ResponseData` class the fields come
from. `ProxyDescriber` asks every hook for it and merges the class's public
properties into the matching operation's response schema as siblings of
`data`. A new augmenting hook needs no edit to the describer (Open/Closed:
extension by implementing the method on the new hook, not by editing a
central list).

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
        - { alias: RegisterUser, type: App\Security\Dto\RegisterUserDto }
```

This has to be the YAML alias list, not a per-controller
`new Model(type: ..., name: '...')` on the `#[OA\RequestBody]` /
`App\OpenApi\Attribute\DataResponse` attribute, for any class used behind
`#[MapRequestPayload]`: Nelmio's `SymfonyMapRequestPayloadDescriber` always
registers that class a second time under its bare class name, independently
of whatever name the controller's own attribute uses, leaving an orphan
unreferenced schema (e.g. `RegisterUserDto` with no `$ref` to it) alongside
the clean one. The YAML alias is checked before any attribute-level name, so
it's the only place that converges both registrations onto one schema. Response-only
classes (no `#[MapRequestPayload]` involved) don't strictly need the YAML
route, but everything stays in `models.names` anyway so there's one mechanism
to look at, not two.

Pick a name that doesn't collide with an existing schema — in particular
anything SpaceTraders' own vendored spec already defines (injected by
`App\SpaceTraders\OpenApi\ProxyDescriber`, see above): `AgentResponseData`
couldn't alias to `Agent` because SpaceTraders already has one, hence
`AccountAgent`. Avoid names of JavaScript/TypeScript globals too, which the
generated types would shadow: hence `ApiError`, not `Error`. Nelmio
silently suffixes a colliding name (`Agent2`) instead of erroring, so check
the dumped spec (`bin/console nelmio:apidoc:dump --format=json`) after
adding an alias to confirm it landed as intended.

**A model reached only through a property ignores its alias:** its schema
is named after the class's short name. Nelmio matches an alias by a hash
that includes the serialization context, and a model reached through a
property is registered with that property's context, so the hash differs
from the alias's (inferred from `vendor/` source, `ClassDescriber` and
`Model::getHash()`, not documented). For such a model (e.g. an enum typed
on a property), name the PHP class after the schema the front should see:
hence the enum is `App\Http\ApiErrorCode`, not `ErrorCode` aliased. Keep its
alias in `models.names` anyway, for the one-mechanism rule above.

## Error codes

The front's codegen gets every possible `error.code` of back-only errors as a
TS union, so it can branch on and translate them.

**Decision:** `App\Http\ErrorResponseData::$code` is typed with the backed
enum `App\Http\ApiErrorCode`, not `string`. Nelmio describes backed enums
natively, as their values; the enum becomes one `ApiErrorCode` component
(named after the class, see "Schema names" above) and `ApiError` plus
every `ErrorResponseData` subclass reference it through `$ref`. `json_encode`
writes a backed enum as its value, so the JSON the front receives is the
same slug as with a plain string.

Why this over generating the enum from the mappers: the type system makes
the spec complete by construction. No code path can build an error body
with a code missing from the spec — including bodies built outside the
mappers (the factory's unmapped fallback).
The accepted cost: every new code adds a case to one central enum, on top
of the new mapper class. That's a data catalog, not a behaviour switch
(nothing `match`es on it), but it is a shared file every error-adding
change touches, and it couples `src/Http/` to every domain's codes.
Revisit if the central enum becomes a bottleneck (many domains adding
codes, merge conflicts on `ApiErrorCode`).

**Per-response code:** the shared `code` schema is the whole enum, and
Swagger UI renders an enum's example as its first case — a wrong code on
almost every endpoint. So `#[ErrorResponse]` requires the code that status
returns and narrows `error` for that response only (`allOf` the shared
model plus a single-value `code` enum carrying that value as `example`).
The front's codegen types that response's error as the exact literal
(`ApiError & { code: '...' }`); `ApiError`, its subclasses and `ApiErrorCode` stay
the shared components. Required rather than optional so an error response
can't be documented without its code; typed as `ApiErrorCode` so it can't name
a code outside the catalog. One code per response for now: when a status
can return several, widen it to a list.

**Validation errors are inferred, not annotated:** an action argument with
`#[MapRequestPayload]` or `#[MapQueryString]` gets its validation error
response from `App\OpenApi\RouteArgumentDescriber\ValidationErrorResponseDescriber`,
at the attribute's own `validationFailedStatusCode` — `422` for
`#[MapRequestPayload]`, `404` for `#[MapQueryString]` unless set — with
`ApiErrorValidation` as model and `validation_failed` as code, built with the
same `ErrorResponse`. Don't add an `#[ErrorResponse]` for it on the
controller: the status comes from the attribute that decides it at runtime,
so the two can't drift. The split: what follows from the action's signature
is inferred; what follows from its logic (the exceptions its service throws)
is annotated. If an operation documents that status by hand anyway, the
hand-written response wins.

**Authentication errors are inferred, not annotated:** every operation
whose `security` requires `bearerAuth` gets the `401`
`authentication_required` response from
`App\OpenApi\Describer\AuthenticationErrorResponseDescriber`, built with the
same `ErrorResponse`. The 401 follows from the firewall, not from the
action's logic, so it is never annotated by hand; an operation that
documents its `401` by hand keeps it. The `security` itself comes from
`#[IsGranted]` on back-only actions (Nelmio's `SecurityDescriber`, inferred
from `vendor/` source) and from `ProxyDescriber` on proxied ones: an action
protected only by `access_control`, without `#[IsGranted]`, ends up with
neither. `tests/OpenApi/AuthenticationErrorResponseTest` checks every
operation requiring the JWT documents it.

Transport errors — a body that can't be decoded or lacks a required key
(`400`), or an unsupported `Content-Type` (`415`) — are deliberately not
documented, whether they come from the argument resolver or from
`json_login` on `/auth/login`. They come out through
`HttpExceptionErrorMapper` as `http_error`, and the
front handles them as any unknown error (see "Front contract" below): a
generated client always sends a well-formed body with its content type, so
listing them on every endpoint with a payload would add noise to the
contract, not information.

`tests/OpenApi/ApiErrorCodeSchemaTest` guards the spec side (every case listed,
every documented error narrowed to one known code). Whether a narrowed code
matches what the endpoint actually returns is the endpoint's functional
test (see "Adding a mapper" in [errors.md](errors.md)).

**Scope:** back-only errors only. Relayed SpaceTraders errors keep their
own numeric `code`, untouched (see [spacetraders-proxy.md](spacetraders-proxy.md)),
and never go through `ErrorResponseData`; they must not be added to this
enum (see the open `code` type TODO in [errors.md](errors.md)).

**Front contract:** the union is closed in the spec, but the back can
deploy a new code before the front regenerates its types. The front must
treat `code` as open: check it against the known values and fall back to
`message` (always safe to show) or a generic per-status text otherwise. A
client generator that validates responses at runtime against the schema
would reject an unknown value; keep that validation off for error bodies.
OpenAPI 3.0 has no standard "open enum"; extensions like
`x-extensible-enum` are conventions, not supported by Nelmio or most
generators.

## TODO

- No command re-fetches/re-bundles `resources/spacetraders/openapi.json` yet;
  it's a manual re-export from SpaceTraders' spec when it changes.
- Expand `App\SpaceTraders\OpenApi\ProxyDescriber::OPERATIONS` beyond its
  current representative subset.
