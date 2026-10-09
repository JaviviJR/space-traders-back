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

In both cases the request and response shapes are exactly the ones documented
by SpaceTraders. The back does not add, remove or rename fields. Changing this
is a deliberate, documented decision.

The back returns the SpaceTraders status code and body to the front unchanged.
Response headers are forwarded too, except transport headers that belong to the
connection between the back and SpaceTraders: hop-by-hop headers (RFC 7230
§6.1), `Content-Length` and `Content-Encoding`, which Symfony recalculates.

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

- **`SpaceTradersRouteHookInterface`**: `supports(Request, string $path):
  bool`, `beforeForward(Request, string $path, ?Agent, User): ?Response`
  (returning a `Response` short-circuits — SpaceTraders is never called),
  `afterForward(Request, string $path, ?Agent, User, ResponseInterface):
  ?Response` (`null` = relay the SpaceTraders response unchanged, a
  `Response` = override what the front receives; side effects — persisting
  something, dispatching an event — belong here either way). `$agent` is
  `null` whenever the request has no `X-Agent-Id` (public endpoints,
  registration). `$user` is the authenticated back user (the proxy route
  requires `ROLE_USER` like everything else outside the public auth
  endpoints), needed by hooks that must act on the user even without an
  `Agent` yet, like registration.
- **`SpaceTradersResponseRelay`**: the single place that turns a SpaceTraders
  `ResponseInterface` into the Symfony `Response` sent to the front (status,
  body, the header-filtering rule above). Used for every request, with or
  without a hook, so pass-through and "with logic" endpoints share the exact
  same conversion path.
- **`SpaceTradersProxyService`**: orchestrates one forwarded request —
  resolves `?Agent` from `X-Agent-Id` if present (404/403 as described
  above), picks the first hook whose `supports()` matches (or none), runs
  `beforeForward`/`afterForward` around the call to the gateway (substituting
  `Authorization` with the resolved agent's decrypted token only when there
  is one), and relays the response unless a hook overrode it.
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
  hook tests fake against instead of spinning up `MockHttpClient` each time.

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

Pending. Defined once there is implemented code that produces errors.

## Tests and contract

The front's e2e tests intercept requests to the back with `page.route()`, so
the mocks follow this back's contract. Keep the contract stable and documented.

Later, a few e2e tests will run against the real back for critical flows.

## TODO

- Errors (see above).
- Base URL of the back for the front (`VITE_API_URL`) and the Vite dev proxy.
- SpaceTraders rate limiting (see "Rate limiting" above).
- Redact `data.token` from the agent registration response instead of
  relaying it to the front unchanged (see "Back-only endpoints" above).
