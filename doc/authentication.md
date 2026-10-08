# Authentication

Status: implemented (registration, login, refresh, logout, agent linking).

## Model

An app user registers with email and password, independently of any
SpaceTraders agent — a user can exist with zero agents. A user can link
**several** agents (1:N). See `doc/architecture.md` for how agent-scoped
SpaceTraders requests pick which agent to act on.

The SpaceTraders agent token is stored only in the back, encrypted. The front
never sees it.

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

- `stateless: true`, with the Lexik JWT authenticator.
- Public (no JWT required): `/auth/register`, `/auth/login`, `/auth/refresh`,
  `/auth/logout`. Everything else requires `ROLE_USER`.

## Testing

`.env.test` has a dedicated JWT keypair (`config/jwt/private-test.pem` /
`public-test.pem`) and a dedicated `AGENT_TOKEN_ENCRYPTION_KEY`, committed on
purpose — not secrets, just fixtures, so tests run without any local setup
(`.env.local` isn't loaded in the `test` environment by Symfony convention).
Never reuse these values outside tests.

## TODO

- Authentication error format (fixed by the back's general error-format
  decision, still open — see `doc/architecture.md`).
- Login rate limiting / brute-force protection (password policy itself is
  decided: minimum 8 characters, enforced by `RegisterUserDto`).
- Login bypasses Symfony's authenticator system entirely (`LoginService`
  validates credentials by hand, no `Passport`/`AuthenticatorManager`). This
  means `UserCheckerInterface` and other authenticator-level extension points
  don't apply to login. Migrate login to a proper Symfony `Authenticator`
  instead of hand-rolling credential checks — don't reinvent what the
  framework already provides.
