# API contract

What the front can rely on. The front (Vue + Pinia SPA) only talks to this back,
never to `api.spacetraders.io`, and its e2e tests mock this contract with
`page.route()`, so changing anything here is a breaking change for the front.

The generated OpenAPI spec (`/api/doc`) is the source of truth for shapes; this
file covers the guarantees and semantics a schema can't express.

## Two kinds of endpoints

- **Back-only:** the back owns the contract (registration, login, refresh,
  logout, email confirmation, `GET /api/agents`). Success is `{"data": ...}`,
  failure is `{"error": {"code", "message"}}`.
- **Proxied SpaceTraders:** everything under `/api/spacetraders/{path}`,
  including `POST /api/spacetraders/register`. The contract is SpaceTraders'
  own. The deciding question is who owns the contract, not whether
  SpaceTraders is called over the network.

## Proxied endpoints

- SpaceTraders' status code is relayed unchanged.
- SpaceTraders' own top-level keys (`data` on success, `error` on failure) are
  never removed, renamed or overridden. The back may add sibling top-level
  fields and its own headers.
- SpaceTraders' response headers are not forwarded; don't depend on them.
- The front's `Authorization` header is never forwarded; the back uses the
  selected agent's token.
- `X-Agent-Id` selects which of the user's agents acts. It is optional: some
  SpaceTraders endpoints take no agent (server status, public data,
  `/register`). Without it, the request is forwarded with no agent token. The
  back keeps no list of which endpoints need an agent: SpaceTraders is the
  authority. An unknown agent is a `404`; another user's agent is a `403`.
- Relayed SpaceTraders errors keep SpaceTraders' numeric `error.code`, not one
  of this back's string codes.

## Back-only errors

- `error.code` is a stable slug from `ApiErrorCode`; branch on it, not on
  `message`. `message` is always safe to show, and is in English.
- **Treat `code` as an open set.** The back can ship a new code before the
  front regenerates its types: fall back to `message` or a per-status text for
  an unknown one. Don't validate error bodies against the schema at runtime.
- Only the per-field messages in a validation error's `violations` follow
  `Accept-Language` (`en`, `es`).
- Transport errors (`400` undecodable or incomplete body, `415` wrong
  `Content-Type`) come out as `http_error` and are deliberately left out of the
  spec: a generated client never sends them.

## Authentication

- CORS allows credentials only under `/auth/`, where the refresh token cookie
  lives; `/api/*` works without credentials.
- `POST /auth/login`:
  - `401 invalid_credentials` (wrong password or unknown email, same body) is a
    form error. Don't try `/auth/refresh` on it.
  - `403 email_not_verified` only when the credentials are correct.
- `401 authentication_required` on any other endpoint (missing, expired or
  rejected JWT): call `/auth/refresh` once, then send the user to login.
- A session's expiry is fixed at login; a refresh never extends it.
- The confirmation email links to `{FRONT_URL}/confirm-email?...`. The front
  forwards those query parameters unchanged to `POST /auth/confirm-email`. Any
  failure is the same `400 invalid_email_confirmation`.
