# Back architecture

## Context

The back is a *Backend for Frontend* (BFF). The front (Vue + Pinia SPA) only
talks to this back, never to `api.spacetraders.io`.

- The back calls SpaceTraders with the agent token. That token lives only in
  the back, encrypted, and the front never sees it.
- SpaceTraders universe resets invalidate agent tokens. The back handles them:
  it revokes the affected sessions and notifies the user.
- The front authenticates against the back. See [authentication.md](authentication.md).
- The back exposes an OpenAPI spec for the front to consume. See
  [openapi.md](openapi.md).

## Contract

This back is a Backend for Frontend (BFF) for the Vue front. It exposes two
kinds of endpoints.

### SpaceTraders endpoints

Requests that target SpaceTraders are forwarded by the back. Each one is either:

- **Pass-through:** forwarded without changes. This is the default.
- **With logic:** the back runs its own logic before and/or after forwarding.

The back relays SpaceTraders' status code unchanged. The top-level keys
SpaceTraders put in its own response body (`data` on success, `error` on
failure) are never touched — not removed, not renamed, not overridden. Beyond
that one guarantee, the back is free to shape the response: add sibling
top-level fields, or set its own response headers. SpaceTraders' response
headers are not forwarded at all; the front must not depend on them.

A sibling field colliding with one of SpaceTraders' own top-level keys is a
bug, not a runtime condition to resolve silently — `App\SpaceTraders\Response\EnvelopeBuilder`
(see "Proxy design" below) throws rather than letting either side win
unpredictably.

The front's `Authorization` header is never forwarded; the back uses the agent
token instead.

A user can link several agents (see [authentication.md](authentication.md)).
The front selects which one a SpaceTraders-proxied request acts on via a
custom `X-Agent-Id` header. The back resolves it to an `Agent` owned by the
authenticated user (404 if it doesn't exist, 403 if it belongs to someone
else) before forwarding anything.

`X-Agent-Id` is optional, not required. Some SpaceTraders endpoints don't
take an agent at all (e.g. server status). If the header is absent, the back
forwards without substituting any `Authorization`. The back doesn't keep its
own list of which SpaceTraders endpoints need an agent — SpaceTraders is the
authority on that, and duplicating it here would drift out of sync.

#### Rate limiting (pending)

SpaceTraders rate-limits requests per agent. Every call this back makes to
SpaceTraders — through the catch-all proxy, or from any other service —
goes through the one shared gateway below (`App\SpaceTraders\Proxy\GatewayInterface`),
not `HttpClientInterface` directly, so there is a single choke point to add
rate limiting to. Not implemented yet (`symfony/rate-limiter` isn't
installed): the gateway today only centralizes the SpaceTraders base URL and
translates transport-level failures. Build the actual per-agent limiting
once the proxy has run for a while and the real call patterns are visible.

#### Proxy design

One catch-all route (`/api/spacetraders/{path}`) handles every SpaceTraders
endpoint, including ones that don't act on an agent at all: public
SpaceTraders data (server status, systems/waypoints/factions, public agent
listing) and `POST /register` (creates the agent this back doesn't have yet).
That's why `X-Agent-Id` is optional for the proxy as a whole, not just for
some endpoints.

Per-endpoint logic is opt-in via the Strategy pattern: a route implements
`App\SpaceTraders\RouteHookInterface` (`supports()` picks it, `beforeForward()` can
short-circuit before SpaceTraders is called, `afterForward()` transforms the
response on its way out) and is auto-tagged
(`#[Autoconfigure(tags: ['app.space_traders_route_hook'])]` on the interface
itself) — no edits to the controller or to other hooks.
`App\SpaceTraders\Response\AugmentInterface` is a separate, opt-in extension of
that interface for the common case of only adding sibling fields/headers
instead of replacing the response — kept apart from `afterForward()` because
it's structurally guaranteed to never touch SpaceTraders' own top-level keys,
and it has a doc-time counterpart (`describedAugmentation()`) the OpenAPI
describer for proxied routes uses — see [openapi.md](openapi.md).

No Symfony HTTP type crosses a layer that doesn't own it: `Request` dies in
`App\Controller\SpaceTraders\SpaceTradersProxyController` (translated into a `App\SpaceTraders\Proxy\ProxyRequest`
before the service ever sees it), and HttpClient's `ResponseInterface` dies
inside `App\SpaceTraders\Proxy\Gateway` (resolved into an `HttpResponseEnvelope`).
`HttpResponseEnvelope` is the one shape used end-to-end — the raw upstream
response coming out of the gateway, and the final shape both the proxy and
every back-only endpoint converge on going out —
`EnvelopeResponseFactory::toJsonResponse()` is the only place in the app that
builds a Symfony `JsonResponse`.

Merging a hook's extra fields into SpaceTraders' raw body is
`App\SpaceTraders\Response\EnvelopeBuilder`'s job, the only consumer of that logic:
pass-through untouched when there's nothing to add; otherwise decode just
enough to merge the extra fields in as siblings, throwing
`EnvelopeMergeException` on a key collision or on an upstream body that
doesn't decode to a JSON object — both are bugs, not conditions to silently
resolve. `EnvelopeResponseFactory` doesn't know about any of this: its
back-only `success()`/`error()` just `json_encode` their payload directly —
a response with nothing to protect doesn't need the merge machinery.

`App\SpaceTraders\Proxy\ProxyService` orchestrates one forwarded request: resolves
`?Agent` from `agentId` if present (404/403 as described above), picks the
first matching hook (if any), runs `beforeForward`/`afterForward` around the
gateway call (substituting `Authorization` with the resolved agent's
decrypted token only when there is one), asks the hook for extra data when it
implements `App\SpaceTraders\Response\AugmentInterface`, and hands the result to
`App\SpaceTraders\Response\EnvelopeBuilder`.

`App\SpaceTraders\Proxy\GatewayInterface` / `App\SpaceTraders\Proxy\Gateway` wraps
`HttpClientInterface`: a 4xx/5xx from SpaceTraders is relayed as-is, not
translated — only genuine transport failures (timeout, DNS, connection)
become `App\SpaceTraders\Exception\ApiException`. Kept as an interface despite one
implementation because it's the test seam the proxy/hook tests fake against,
instead of spinning up `MockHttpClient` each time.

### Back-only endpoints

Endpoints whose contract the back owns, instead of relaying SpaceTraders'
contract unchanged. This is the deciding question, not whether SpaceTraders
gets called over the network: user registration, login, token refresh and
logout never touch SpaceTraders — their contract is entirely ours.

`GET /api/agents` is also back-only: it lists the authenticated user's
linked agents straight from this back's DB (`id`, `symbol`, `faction`,
`createdAt`), no SpaceTraders call involved.

**Agent registration (`POST /api/spacetraders/register`) is not back-only.**
It goes through the catch-all proxy like any other SpaceTraders endpoint
(see "Proxy design" above) — the front talks to SpaceTraders' real
`/register` contract directly, this back doesn't hand-build or reshape the
response; the only back-side behavior is a hook persisting the new `Agent`
with its encrypted token as a side effect. **Known simplification:**
SpaceTraders' raw response, including the plaintext agent token, currently
reaches the front unredacted — see "Pending" below.

## Errors

A single `kernel.exception` listener (`ExceptionListener`) converts every
uncaught exception into the same envelope as any other response —
`{"error": {"code", "message"}}` — via `EnvelopeResponseFactory::error()`,
instead of Symfony's default error shape. `code` is a stable,
machine-readable identifier for the front to branch on; `message` is
human-readable and safe to expose.

Deciding the response for a given exception is delegated to
`ExceptionErrorMapperInterface` (`supports()` + `map()`): one class per kind
of exception, tagged `app.exception_error_mapper` and injected into the
listener as a priority-ordered `#[AutowireIterator]`, trying each
`supports()` in order and using the first match — the same shape Symfony
itself uses for Normalizers. `map()` returns a `MappedError`: the HTTP
status plus the public body as an `ErrorResponseData`, which the listener
hands to the envelope as-is.

Anything unmapped → `500`, `internal_error`, a generic message (the real
exception is never exposed). `500` is reserved for the back's own internal
failures; anything else that should reach the front gets a mapper.

### Adding a mapper

1. Create a class in `src/EventListener/ErrorMapper/` implementing
   `ExceptionErrorMapperInterface`. The interface carries
   `#[AutoconfigureTag]`, so autoconfiguration registers it; no service
   definition needed.
2. `supports()` matches the exception (usually an `instanceof`); `map()`
   returns its status, a stable `code` (a `snake_case` slug, kept in a
   `CODE` constant) and the public message.
3. Choose the message deliberately: a fixed text by default. Reuse
   `getMessage()` only when the exception's message is written to be shown
   to the front (as `HttpExceptionInterface` messages are); a domain
   exception's message is not a public contract.
4. If it must run ahead of a more generic mapper that would also match, raise
   its priority with `#[AsTaggedItem(priority: ...)]`; with none, it runs
   after every prioritised one.
5. If the error needs fields beyond `code`/`message` (e.g. per-field detail
   the front binds to a form), put them in a `readonly` subclass of
   `ErrorResponseData` in `src/Http/` and return that as the body — not a
   nullable field on `ErrorResponseData`, which every other error would then
   carry. The rest of the pipeline is unchanged: the subclass is still an
   `ErrorResponseData`. Document it on the affected endpoints with
   `#[ErrorResponse(..., model: TheSubclass::class)]` (see
   [openapi.md](openapi.md)).
6. If the error response needs headers (e.g. a `Set-Cookie` clearing a
   rejected credential, or headers an `HttpExceptionInterface` carries), return
   them in `MappedError::$headers`; the listener passes them to the envelope.
   A mapper may inject whatever HTTP-layer service it needs to build them.
7. Add a unit test in `tests/EventListener/ErrorMapper/` (supports, doesn't
   support another exception, maps to the expected status/code/message), and
   assert the `code` in the functional test of the endpoint that triggers it.

Anything specific to one mapper (why that status, why that priority, what it
deliberately doesn't match) is documented in the mapper's own docblock, not
here.

### Localized validation messages

Only the per-field messages in a validation error's `violations` follow the
request's `Accept-Language` header. Everything else — the envelope's `code`
and `message` (`Validation failed.` included), every other mapper's
message, and relayed SpaceTraders errors — stays in English.

This relies on the framework, not on code in the mapper:
`framework.set_locale_from_accept_language` sets the request locale, and the
Validator translates constraint messages with the `validators` catalogs
shipped by `symfony/validator` (`config/packages/translation.yaml`).

- Enabled locales are `en` and `es` (`framework.enabled_locales`). A missing
  header keeps `default_locale` (`en`); an unsupported one gets the *first*
  enabled locale, so `en` must stay first. A regional variant (`es-AR`)
  matches its language (`es`).
- The response carries `Vary: Accept-Language` (added by the framework to
  every response once the option is on). No `Content-Language`: most of the
  body, including the envelope's `message`, isn't in the request locale.
- A custom constraint message (`#[Assert\...(message: ...)]`) must be a
  translation key with entries in `translations/validators.{en,es}.*`;
  plain English text there is never translated.
- Type mismatches reported by `#[MapRequestPayload]` (`This value should be
  of type ...`) are translated too; templates with no entry in the catalogs
  stay in English.

### Controllers don't translate exceptions

Controllers and services let domain exceptions propagate to the listener;
they don't `catch` them to rethrow an `HttpException`. Services stay
HTTP-agnostic, and every error response is decided in one place.

The one exception: when the same exception needs a different response
depending on the endpoint (e.g. "user not found" is a `404` on a lookup but
must be a generic `401` on login, to avoid revealing which emails exist), a
global mapper can't express it. Prefer having the service throw distinct
exceptions; only if that isn't reasonable, translate in that specific
controller.

## CORS

The front (Vue + Pinia SPA) is served from a different origin than the back,
so cross-origin requests need CORS headers. Handled by `NelmioCorsBundle`
(`config/packages/nelmio_cors.yaml`), not hand-written headers in
controllers or the kernel.

Allowed origin is `%env(CORS_ALLOW_ORIGIN)%`, currently set in the committed
`.env` to `^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$` — any port on
localhost/127.0.0.1, since the front's dev port can vary. This is a dev-only
default.

## Tests and contract

The front's e2e tests intercept requests to the back with `page.route()`, so
the mocks follow this back's contract. Keep the contract stable and documented.

Later, a few e2e tests will run against the real back for critical flows.

## TODO

- When a production environment exists, restrict `CORS_ALLOW_ORIGIN` to the
  real production domain only (set per-environment, not in the committed
  `.env` default — see "CORS" above).
- Base URL of the back for the front (`VITE_API_URL`) and the Vite dev proxy.
- SpaceTraders rate limiting (see "Rate limiting" above).
- Known leak of localized messages, accepted for now: once a translator
  exists, LexikJWTAuthenticationBundle translates its own authentication
  failure message with the `security` domain. Its own keys (`Invalid JWT
  Token`, `Expired JWT Token`) have no catalog entry and stay in English, but
  a valid JWT for a deleted user returns Symfony's `Username could not be
  found.` translated (e.g. Spanish with `Accept-Language: es`). Revisit if it
  causes problems — e.g. a listener on Lexik's `JWT_INVALID`/`JWT_EXPIRED`
  events that resets the untranslated message.
- Redact `data.token` from the agent registration response instead of
  relaying it to the front unchanged (see "Back-only endpoints" above).
- `error.code`'s type is inconsistent across the two response kinds: a
  string slug for back-only errors (`ErrorResponseData`), a number from
  SpaceTraders' own catalog for relayed proxy errors (see
  https://spacetraders.io/api-guide/response-errors). Not decided yet: how
  (or whether) to unify the type — e.g. casting SpaceTraders' numeric `code`
  to string on the way out — without losing the pass-through optimization
  for responses that don't need it.
- Related, also not decided: whether the HTTP status code the front
  receives for a relayed SpaceTraders error should keep being SpaceTraders'
  own status unchanged for every case (today's guarantee, see "SpaceTraders
  endpoints" above), or be decided per error. Changing this would mean
  revisiting that existing guarantee, not just adding to it.
