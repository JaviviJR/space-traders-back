# SpaceTraders proxy

How the back forwards SpaceTraders endpoints, one of the two endpoint kinds
described in "Contract" in [architecture.md](architecture.md).

## SpaceTraders endpoints

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

## Rate limiting (pending)

SpaceTraders rate-limits requests per agent. Every call this back makes to
SpaceTraders — through the catch-all proxy, or from any other service —
goes through the one shared gateway below (`App\SpaceTraders\Proxy\GatewayInterface`),
not `HttpClientInterface` directly, so there is a single choke point to add
rate limiting to. Not implemented yet (`symfony/rate-limiter` isn't
installed): the gateway today only centralizes the SpaceTraders base URL and
translates transport-level failures. Build the actual per-agent limiting
once the proxy has run for a while and the real call patterns are visible.

## Proxy design

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

## TODO

- SpaceTraders rate limiting (see "Rate limiting" above).
