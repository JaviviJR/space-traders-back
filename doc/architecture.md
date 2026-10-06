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
