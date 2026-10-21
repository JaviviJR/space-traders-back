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

Requests that target SpaceTraders are forwarded by the back through a
catch-all proxy. Contract guarantees, agent selection (`X-Agent-Id`), rate
limiting and proxy design: see [spacetraders-proxy.md](spacetraders-proxy.md).

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
(see "Proxy design" in [spacetraders-proxy.md](spacetraders-proxy.md)) — the
front talks to SpaceTraders' real `/register` contract directly, this back
doesn't hand-build or reshape the response; the only back-side behavior is a
hook persisting the new `Agent` with its encrypted token as a side effect.
**Known simplification:** SpaceTraders' raw response, including the
plaintext agent token, currently reaches the front unredacted — see "TODO"
below.

## Errors

Error envelope, exception mappers and localized validation messages: see
[errors.md](errors.md).

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
- Redact `data.token` from the agent registration response instead of
  relaying it to the front unchanged (see "Back-only endpoints" above).
