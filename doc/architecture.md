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
goes through the one shared gateway below (`SpaceTradersGatewayInterface`),
not `HttpClientInterface` directly, so there is a single choke point to add
rate limiting to. Not implemented yet (`symfony/rate-limiter` isn't
installed): the gateway today only centralizes the SpaceTraders base URL and
translates transport-level failures. Build the actual per-agent limiting
once the proxy has run for a while and the real call patterns are visible.

#### Proxy design

One catch-all route handles every SpaceTraders endpoint
(`/api/spacetraders/{path}`), including ones that don't act on an agent at
all: public SpaceTraders data (server status, systems/waypoints/factions,
public agent listing — no token required) and `POST /register` (takes an
`AccountToken`, not an `AgentToken`, and creates the agent this back doesn't
have yet). Because of that, `X-Agent-Id` is optional for the proxy as a
whole, not just for some endpoints — resolution can legitimately produce no
agent. Per-endpoint logic is opt-in, added without touching the controller
or any other endpoint's code (Strategy pattern):

No Symfony HTTP type crosses a layer that doesn't own it. The `Request`
dies in `SpaceTradersProxyController`, which translates it into a
`SpaceTradersProxyRequest` before calling the service — hooks and the
service never see a Symfony `Request`. Symmetrically, the HttpClient
`ResponseInterface` dies inside `SpaceTradersGateway`, which resolves it
(status, body, headers) into an `HttpResponseEnvelope` before returning —
nothing downstream depends on HttpClient's lazy response type, or risks a
transport exception outside the one place that already catches it.
`HttpResponseEnvelope` (status code + body as a string + headers, in
`App\Http`) is the one type used at both ends of this pipeline — as the raw
upstream response coming out of the gateway, and as the final shape both
this proxy and every back-only endpoint converge on going out —
`EnvelopeResponseFactory::toJsonResponse()` is the only place in the app
that builds a Symfony `JsonResponse`.

- **`SpaceTradersProxyRequest`**: `method`, `path`, `query`, `headers`
  (already filtered to the forwardable subset), `body` (raw, undecoded —
  a hook that needs specific fields decodes it itself), `agentId` (the raw
  `X-Agent-Id` header value, not yet resolved), `user`.
- **`SpaceTradersRouteHookInterface`**: `supports(SpaceTradersProxyRequest):
  bool`, `beforeForward(SpaceTradersProxyRequest, ?Agent):
  ?HttpResponseEnvelope` (non-null short-circuits — SpaceTraders is never
  called), `afterForward(SpaceTradersProxyRequest, ?Agent,
  HttpResponseEnvelope): HttpResponseEnvelope` (non-nullable: by this
  point SpaceTraders has already been called, so this never short-circuits
  anything — it transforms the response on its way out, e.g. redacting a
  field inside `data`, and returns the same response unchanged when there's
  nothing to do; side effects — persisting something, dispatching an event —
  belong here either way). `$agent` is `null` whenever the request has no
  `X-Agent-Id` (public endpoints, registration); the authenticated user is
  always available via `$request->user`.
- **`SpaceTradersResponseAugmenterInterface`**: opt-in, on top of
  `SpaceTradersRouteHookInterface`, for the common case of adding to the
  response instead of replacing it — unlike `afterForward`, this can only add
  sibling fields, never touch SpaceTraders' own top-level keys.
  `augment(SpaceTradersProxyRequest, ?Agent, HttpResponseEnvelope):
  ?SpaceTradersResponseAugmentation` is called with the response
  `afterForward` produced; it returns extra top-level body fields and/or
  extra response headers (e.g. echoing `X-Agent-Id` back), or `null` to add
  nothing. Most hooks don't implement this. Also declares
  `describedAugmentation(): SpaceTradersAugmentationSchema`, the doc-time
  counterpart the OpenAPI describer for proxied routes uses to merge these
  extra fields into the documented schema — see [openapi.md](openapi.md).
- **`SpaceTradersEnvelopeBuilder`**: turns the final `HttpResponseEnvelope`
  (what the gateway hands back, and what travels through
  `afterForward`/`augment` — `statusCode`, `body` undecoded, `headers`) plus
  an optional augmentation into the `HttpResponseEnvelope` the controller
  hands to `EnvelopeResponseFactory::toJsonResponse()`. Without an
  augmentation (the common, pass-through case) the body passes through
  untouched — no decode/re-encode. An augmentation forces a decode so the
  extra fields can be merged in (SpaceTraders' own keys always win on
  collision, so `data`/`error` can't be overridden this way). Deliberately
  not a named constructor on `HttpResponseEnvelope`: that type is generic
  (shared with back-only responses) and stays unaware of SpaceTraders-specific
  concepts, even though the proxy also uses it for the raw upstream response.
- **`SpaceTradersProxyService`**: orchestrates one forwarded request —
  resolves `?Agent` from `agentId` if present (404/403 as described above),
  picks the first hook whose `supports()` matches (or none), runs
  `beforeForward`/`afterForward` around the call to the gateway (substituting
  `Authorization` with the resolved agent's decrypted token only when there
  is one), asks the hook for an augmentation (when it implements that
  interface) if `beforeForward` didn't short-circuit, and hands the result to
  `SpaceTradersEnvelopeBuilder`.
- **Registering a hook**: implement the interface and it's auto-tagged
  (`#[Autoconfigure(tags: ['app.space_traders_route_hook'])]` on the
  interface itself) — no edits elsewhere. The service receives all hooks via
  `#[AutowireIterator]`.
- **`SpaceTradersGatewayInterface`** / `SpaceTradersGateway`: wraps
  `HttpClientInterface`. Pass-through means a 4xx/5xx from SpaceTraders is
  relayed as-is, not translated — the gateway only translates genuine
  transport failures (timeout, DNS, connection) into
  `SpaceTradersApiException`. Has an interface, despite a single
  implementation, because it's the test seam `SpaceTradersProxyService` and
  hook tests fake against instead of spinning up `MockHttpClient` each time
  — the fake builds a plain `HttpResponseEnvelope`, no HttpClient involved
  at all.

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

A single `kernel.exception` listener (`App\EventListener\ExceptionListener`)
converts every uncaught exception into the same envelope as every other
response — `{"error": {"code", "message"}}` — through
`EnvelopeResponseFactory::error()`, instead of Symfony's default error shape.
`code` is a stable, machine-readable identifier for the front to branch on;
`message` is human-readable and safe to expose. Controllers keep throwing
whatever exception already makes sense (they're untouched by this); the
listener only wires the kernel hook to the envelope — it doesn't know how
any particular exception should be described.

Deciding the `(status, code, message)` for a given exception is delegated to
`App\EventListener\ErrorMapper\ExceptionErrorMapperInterface`: one small
class per kind of exception, each with `supports(\Throwable): bool` and
`map(\Throwable): MappedError`. The interface is tagged
(`#[AutoconfigureTag('app.exception_error_mapper')]`), and the listener
receives every implementation as a `#[AutowireIterator('app.exception_error_mapper')]`,
trying each one's `supports()` in order and using the first match's `map()`.
This is the same shape Symfony itself uses for Normalizers
(`supportsNormalization()`) or Messenger transport factories: adding a new
exception case means adding a new mapper class, not editing a growing
`match`/switch in the listener (open/closed). A mapper that needs to run
ahead of a more generic one (see below) raises its priority with
`#[AsTaggedItem(priority: ...)]`; with no explicit priority a mapper runs
last, in registration order.

Mappers, in evaluation order:

- `ValidationFailedExceptionErrorMapper` (priority `10`) — a failed
  `#[MapRequestPayload]` / `#[MapQueryString]` validation → the exception's
  own status (normally `422`), `validation_failed`, one `"field: problem"`
  pair per violation joined with `; ` (e.g. `"password: This value is too
  short. It should have 8 characters or more."`).
- `HttpExceptionErrorMapper` (default priority) — fallback for any other
  exception already implementing `HttpExceptionInterface` (what controllers
  already throw today: `UnauthorizedHttpException`,
  `AccessDeniedHttpException`, `NotFoundHttpException`, ...) → its own
  `getStatusCode()` and `getMessage()`, both already intentional and safe —
  set by the code that threw it. Runs after the validation mapper above,
  which would otherwise also match the wrapping `HttpException`.
- `SpaceTradersApiExceptionErrorMapper` (default priority) — the gateway's
  genuine transport failures (timeout, DNS, connection; **not** a relayed
  SpaceTraders 4xx/5xx) → `502`, `spacetraders_unreachable`, a generic
  message. The back acted as a proxy and the upstream didn't respond; `500`
  is reserved for the back's own internal failures.

Anything no mapper supports (unexpected) → `500`, `internal_error`, a
generic message, hardcoded as the listener's own fallback. The real
exception message is never exposed, to avoid leaking internal details.

Each mapper's `code` is a `private const string CODE` on the class, not a
literal repeated at each `new MappedError(...)` call site.

## Tests and contract

The front's e2e tests intercept requests to the back with `page.route()`, so
the mocks follow this back's contract. Keep the contract stable and documented.

Later, a few e2e tests will run against the real back for critical flows.

## TODO

- Base URL of the back for the front (`VITE_API_URL`) and the Vite dev proxy.
- SpaceTraders rate limiting (see "Rate limiting" above).
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
