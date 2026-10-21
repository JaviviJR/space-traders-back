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

`ErrorResponse` also takes a required `code:` (an `App\Http\ErrorCode`
case): the code that status returns on that endpoint. See "Per-response
code" under "Error codes" below for why it's required.


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
`AccountAgent`. Nelmio silently suffixes a colliding name (`Agent2`) instead
of erroring, so check the dumped spec
(`bin/console nelmio:apidoc:dump --format=json`) after adding an alias to
confirm it landed as intended.

## Error codes

The front's codegen gets every possible `error.code` of back-only errors as a
TS union, so it can branch on and translate them.

**Decision:** `App\Http\ErrorResponseData::$code` is typed with the backed
enum `App\Http\ErrorCode`, not `string`. Nelmio describes backed enums
natively, as their values; the enum becomes one `ErrorCode` component
(aliased in `models.names` like every other model) and `Error` plus every
`ErrorResponseData` subclass reference it through `$ref`. `json_encode`
writes a backed enum as its value, so the JSON the front receives is the
same slug as with a plain string.

Why this over generating the enum from the mappers (see "Alternative
considered" below): the type system makes the spec complete by
construction. No code path can build an error body with a code missing
from the spec — including bodies built outside the mapper pipeline (the
unmapped fallback in `ExceptionListener`, the security failure handlers).
The accepted cost: every new code adds a case to one central enum, on top
of the new mapper class. That's a data catalog, not a behaviour switch
(nothing `match`es on it), but it is a shared file every error-adding
change touches, and it couples `src/Http/` to every domain's codes.

**Per-response code:** the shared `code` schema is the whole enum, and
Swagger UI renders an enum's example as its first case — a wrong code on
almost every endpoint. So `#[ErrorResponse]` requires the code that status
returns and narrows `error` for that response only:
`allOf: [ $ref Error (or the subclass), { code: enum [that one value], example: that value } ]`.
The front's codegen types that response's error as the exact literal
(`Error & { code: '...' }`). The `example` is what makes Swagger UI show
the real code: its `allOf` merge keeps the shared, full enum (it doesn't
override enums by design, see
[swagger-ui#3674](https://github.com/swagger-api/swagger-ui/issues/3674)),
so the narrowing alone still rendered the first case. Checked by running
the Swagger UI bundled with Nelmio (its resolver and sample generator) on
the dumped spec — derived from that `vendor/` code, not from Swagger UI's
docs. Reordering the `allOf` also worked there but only because its merge
lets the first branch win, and an `example` on the whole `error` object
would have to invent the subclass fields (`violations`). Meanwhile
`Error`, its subclasses and `ErrorCode` stay the shared components. Required
rather than optional so an error response can't be documented without its
code; typed as `ErrorCode` so it can't name a code outside the catalog. One
code per response for now: when a status can return several, widen it to a
list.

**Validation errors are inferred, not annotated:** an action argument with
`#[MapRequestPayload]` or `#[MapQueryString]` gets its validation error
response from `App\OpenApi\RouteArgumentDescriber\ValidationErrorResponseDescriber`
(a Nelmio `RouteArgumentDescriberInterface`, auto-tagged by the bundle), at
the attribute's own `validationFailedStatusCode` — `422` for
`#[MapRequestPayload]`, `404` for `#[MapQueryString]` unless set — with
`ValidationError` as model and `validation_failed` as code, built with the
same `ErrorResponse`. Don't add an `#[ErrorResponse]` for it on the
controller: the status comes from the attribute that decides it at runtime,
so the two can't drift. The split: what follows from the action's signature
is inferred; what follows from its logic (the exceptions its service throws)
is annotated. If an operation documents that status by hand anyway, the
hand-written response wins: Nelmio reads controller attributes before
running route argument describers.

**Authentication errors are inferred, not annotated:** every operation
whose `security` requires `bearerAuth` gets the `401`
`authentication_required` response from
`App\OpenApi\Describer\AuthenticationErrorResponseDescriber` (a Nelmio
`DescriberInterface`, priority `-500` so it runs after Nelmio's own
describers), built with the same `ErrorResponse`. The 401 follows from the
firewall, not from the action's logic, so it is never annotated by hand;
an operation that documents its `401` by hand keeps it. The `security`
itself comes from `#[IsGranted]` on back-only actions (Nelmio's
`SecurityDescriber`, inferred from `vendor/` source) and from
`ProxyDescriber` on proxied ones: an action protected only by
`access_control`, without `#[IsGranted]`, ends up with neither.
`tests/OpenApi/AuthenticationErrorResponseTest` checks every operation
requiring the JWT documents it.

The argument resolver's transport errors — a body it can't decode (`400`)
or an unsupported `Content-Type` (`415`) — are deliberately not documented.
They come out through `HttpExceptionErrorMapper` as `http_error`, and the
front handles them as any unknown error (see "Front contract" below): a
generated client always sends a well-formed body with its content type, so
listing them on every endpoint with a payload would add noise to the
contract, not information.

`tests/OpenApi/ErrorCodeSchemaTest` dumps the spec and checks that the
`ErrorCode` schema lists every case, that the error bodies reference it, and
that every documented 4xx/5xx error response narrows `code` to exactly one
known case and carries it as `example` — which also catches an error documented with a hand-written
`#[OA\Response]` instead of `#[ErrorResponse]`. It can't detect the opposite
drift — a case no code path emits anymore — which is harmless for the front
(a documented code that never arrives), nor a narrowed code that doesn't
match what the endpoint actually returns: the functional test of that
endpoint asserts the real `code` (see "Adding a mapper" in
[errors.md](errors.md)).

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

### Alternative considered: enum generated from the mappers

Kept here because it may be revisited if the central enum becomes a
bottleneck (many domains adding codes, merge conflicts on `ErrorCode`).

**How it would work:** a Nelmio `DescriberInterface` (tagged
`nelmio_api_doc.describer.default`, like `ProxyDescriber`) receives the
`app.exception_error_mapper` tagged iterator, collects every mapper's code
and creates the `ErrorCode` component (`type: string`, `enum: [...]`)
itself. `ErrorResponseData::$code` goes back to `string` and points at that
component with a static `#[OA\Property(ref: '#/components/schemas/ErrorCode')]`,
inherited by subclasses. Adding a mapper then needs no edit to any central
file.

Why a describer that *creates* the component rather than one that *patches*
`Error`: describers run before Nelmio registers model schemas, then the
swagger-php processor pipeline runs (`ApiDocGenerator::generate()` —
inferred from `vendor/` source, not documented), so `Error` doesn't exist
yet when a describer runs. A custom swagger-php processor (tag
`nelmio_api_doc.swagger.processor`, also only found in `vendor/`, not in
Nelmio's docs) runs late enough to patch it, but relies on undocumented
machinery.

How each mapper would expose its code, from weakest to preferred:

- A public `CODE` constant read by reflection: nothing enforces it exists.
- A method on `ExceptionErrorMapperInterface`: forces every mapper, but the
  listener (the interface's real consumer) never calls it — an interface
  segregation smell.
- A small separate interface (e.g. `describedCodes(): list<string>`), the
  same shape as `AugmentInterface::describedAugmentation()` for hooks: a
  doc-time counterpart kept apart from the runtime interface, filtered with
  `instanceof`. A list allows a mapper with several codes.

What makes it harder than the current decision:

- Codes emitted outside mappers (the listener's unmapped fallback, security
  failure handlers) need their own source, or they silently vanish from the
  spec. This is the drift the enum rules out by type.
- A mapper that forgets the small interface also silently vanishes; it
  needs a test asserting every `app.exception_error_mapper` service
  implements it.
- The spec test compares the dumped `ErrorCode` enum against the codes
  collected from the registered mappers plus the non-mapper sources.

Switching means: remove `App\Http\ErrorCode` and its `models.names` alias,
type `$code` back as `string` with the `ref`, add the describer and the
small interface, and replace the enum cases used in mappers, the listener
and the failure handlers with each one's own code. `#[ErrorResponse]`'s
`code:` would become a plain string, no longer checked by the type system —
only by the spec test's "exactly one known case" assertion.

## TODO

- No command re-fetches/re-bundles `resources/spacetraders/openapi.json` yet;
  it's a manual re-export from SpaceTraders' spec when it changes.
- Expand `App\SpaceTraders\OpenApi\ProxyDescriber::OPERATIONS` beyond the representative
  subset above.
