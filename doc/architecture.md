# Back architecture

## Context

The back is a *Backend for Frontend* (BFF). The front (Vue + Pinia SPA) only
talks to this back, never to `api.spacetraders.io`.

- The back calls SpaceTraders with the agent token. That token lives only in
  the back, encrypted, and the front never sees it.
- SpaceTraders universe resets invalidate agent tokens. The back handles them:
  it revokes the affected sessions and notifies the user.
- The front authenticates against the back. See [authentication.md](authentication.md).

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

#### Proxy design

One catch-all route handles every SpaceTraders endpoint
(`/api/spacetraders/{path}`). Per-endpoint logic is opt-in, added without
touching the controller or any other endpoint's code (Strategy pattern):

- **`SpaceTradersRouteHookInterface`**: `supports(Request): bool`,
  `beforeForward(Request, Agent): ?Response` (returning a `Response` short-
  circuits — SpaceTraders is never called), `afterForward(Request, Agent,
  ResponseInterface): void` (side effects only; the return value is ignored).
- **`afterForward` returns `void` on purpose.** It lets the type system
  enforce "the back never changes a SpaceTraders response shape" instead of
  relying on each hook author remembering the rule. A hook can react (persist
  something, dispatch an event) but cannot alter what the front receives.
- **`SpaceTradersResponseRelay`**: the single place that turns a SpaceTraders
  `ResponseInterface` into the Symfony `Response` sent to the front (status,
  body, the header-filtering rule above). Used for every request, with or
  without a hook, so pass-through and "with logic" endpoints share the exact
  same conversion path.
- **`SpaceTradersProxyService`**: orchestrates one forwarded request — picks
  the first hook whose `supports()` matches (or none), runs
  `beforeForward`/`afterForward` around the call to SpaceTraders, decrypts the
  agent's token to call it, and relays the response.
- **Registering a hook**: implement the interface and it's auto-tagged
  (`#[Autoconfigure(tags: [...])]` on the interface itself) — no edits
  elsewhere. The service receives all hooks as a tagged iterator.

**If a hook ever needs to actually change the response shape** (e.g.
enriching `GET /my/ships` with a computed field), that is a deliberate
decision, not something that should happen by accident: widen
`afterForward(...): void` to `afterForward(...): ?Response` (`null` = no
change, a `Response` = override), update this file to say so, and only then
implement it. Don't build that capability before a real endpoint needs it.

### Back-only endpoints

Endpoints that do not reach SpaceTraders (registration, login, token refresh,
logout) are defined by this back. They use the same envelope as SpaceTraders:
`{ "data": ... }`.

## Errors

Pending. Defined once there is implemented code that produces errors.

## Tests and contract

The front's e2e tests intercept requests to the back with `page.route()`, so
the mocks follow this back's contract. Keep the contract stable and documented.

Later, a few e2e tests will run against the real back for critical flows.

## Pending

- Errors (see above).
- Base URL of the back for the front (`VITE_API_URL`) and the Vite dev proxy.
