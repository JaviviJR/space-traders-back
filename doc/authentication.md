# Authentication

Status: decided, pending implementation.

## Model

- An app user registers with email and password. (Pending: is the user the
  SpaceTraders agent, or does it have its own account linked to an agent?)
- The SpaceTraders agent token is stored only in the back, encrypted. The front
  never sees it.

## Tokens

| Token | Format | Lifetime | Client side | Server side |
|---|---|---|---|---|
| Access | JWT signed with RS256 | 15 min | Front memory, `Authorization: Bearer` | Not stored |
| Refresh | Opaque random string, 256 bits | 30 days | Cookie `httpOnly; Secure; SameSite=Strict; Path=/auth/refresh` | SHA-256 hash in DB (Doctrine) |

- The access token is issued by `lexik/jwt-authentication-bundle`.
- The refresh token is implemented by us.

## Refresh token rotation

- Each refresh issues a new pair and marks the previous token as used.
- Each refresh token belongs to a **family** (a chain of rotations that starts
  at login).
- If an already-used refresh token arrives, its whole family is revoked
  (possible theft).
- Logout revokes the current family and clears the cookie.
- A SpaceTraders universe reset revokes all families of the affected agents.

## Firewall

- `stateless: true`, with the Lexik JWT authenticator.
- `/auth/refresh` and `/auth/login` do not require an access token.

## Pending

- User/agent model (see Model).
- Authentication error format (fixed by the back contract).
- Password policy and login attempt limits.
