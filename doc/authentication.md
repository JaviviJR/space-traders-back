# Authentication

Status: implemented (registration, login, refresh, logout, agent linking).

## Model

An app user registers with email and password, independently of any
SpaceTraders agent — a user can exist with zero agents. A user can link
**several** agents (1:N). See [spacetraders-proxy.md](spacetraders-proxy.md) for how agent-scoped
SpaceTraders requests pick which agent to act on.

The SpaceTraders agent token is stored only in the back, encrypted. The front
never sees it.

A new `User` has `isVerified = false`. Registration sends a confirmation
email (`symfonycasts/verify-email-bundle`) whose link points at
`{FRONT_URL}/confirm-email?<signature query>`, not at a backend route.
`FRONT_URL` is an env var (e.g. `http://localhost:5173`).

The `<signature query>` (`id`, `expires`, `token`, `signature`) is opaque to
the front: its job is just to land on `{FRONT_URL}/confirm-email`, read those
4 params and forward them, unchanged, as the query string of
`POST /auth/confirm-email`. A missing, extra or altered parameter, an unknown
`id` and a tampered signature all return the same `400`
`invalid_email_confirmation` body, so the endpoint doesn't reveal which part
failed or which user ids exist.

`POST /auth/login` returns **403** `email_not_verified` when the credentials
are correct but `!isVerified`, and only then: an unverified account isn't
revealed to someone without its password. A wrong password or an unknown email is a **401**
`invalid_credentials`, with the same body for both so the endpoint doesn't
reveal which emails exist. The front must not treat that 401 like
`authentication_required`: it's a form error, not a cue to try
`/auth/refresh`.

## Tokens

| Token | Format | Lifetime | Client side | Server side |
|---|---|---|---|---|
| Access | JWT signed with RS256 | 15 min | Front memory, `Authorization: Bearer` | Not stored |
| Refresh | Opaque random string, 256 bits | 30 days | Cookie `httpOnly; Secure; SameSite=Strict; Path=/auth` | SHA-256 hash in DB (Doctrine) |

- The access token is issued by `lexik/jwt-authentication-bundle`.
- The refresh token is implemented by us.
- The cookie's `Path` is `/auth` (not `/auth/refresh`), so it also reaches
  `/auth/login` and `/auth/logout` — logout needs to read it to know which
  session to revoke.

## Refresh token (non-rotating)

Kept simple on purpose: no token family, no reuse/theft detection. Trade-off
accepted: a stolen refresh token is valid until it expires or the user logs
out.

- One refresh token per login. A user can have several active refresh tokens
  at once (e.g. phone + laptop); each login issues a new one, independent of
  the others.
- A refresh call re-validates the same token (not expired, not revoked) and
  issues a new access token. The refresh token itself doesn't change.
- Logout reads the presented cookie and revokes only that one token — the
  other sessions stay valid. No cookie or an unknown hash → idempotent 204,
  no error. Logout does not require the access token (JWT); the cookie alone
  identifies the session.
- A SpaceTraders universe reset revokes all of the affected user's active
  refresh tokens (every session), since sessions belong to the user, not to a
  specific agent.

## Firewall

- `login` (`^/auth/login$`): stateless, Symfony's `json_login` with
  `UserVerificationChecker` as user checker and `LoginSuccessHandler` /
  `LoginFailureHandler` as handlers.
- `main`: `stateless: true`, with the Lexik JWT authenticator.
- Public (no JWT required): `/auth/register`, `/auth/login`, `/auth/refresh`,
  `/auth/logout`, `/auth/confirm-email` (POST), `/auth/resend-confirmation`
  (POST). Everything else requires `ROLE_USER`.
- A missing or rejected JWT (malformed, expired, or for a user that no
  longer exists) returns `401` `authentication_required` in the error
  envelope, with `WWW-Authenticate: Bearer`. All cases share one code: the
  front tries `/auth/refresh` once, then sends the user to login. See
  "Entry points" in [errors.md](errors.md).

## Testing

`.env.test` has a dedicated JWT keypair (`config/jwt/private-test.pem` /
`public-test.pem`) and a dedicated `TOKEN_ENCRYPTION_KEY`, committed on
purpose — not secrets, just fixtures, so tests run without any local setup
(`.env.local` isn't loaded in the `test` environment by Symfony convention).
Never reuse these values outside tests.

## TODO

- Login rate limiting / brute-force protection (password policy itself is
  decided: minimum 8 characters, enforced by `RegisterUserDto`).
