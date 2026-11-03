# Research: Back session ready for the front's login

**Feature**: `specs/001-front-login-session` | **Date**: 2026-11-03

No `NEEDS CLARIFICATION` items remained in the Technical Context. The research covers the open design decisions, the dependencies touched and their integration patterns.

## 1. Where `rememberMe` is read and validated

- **Decision**: A `CheckPassportEvent` listener registered on the `login` firewall's dispatcher (`security.event_dispatcher.login`) with a priority above 2048, so it runs before the user is loaded and the password is checked. It reads the request payload through `RequestStack` and throws `BadRequestHttpException` when `rememberMe` is present and not a boolean. `LoginSuccessHandler` then reads the already-validated value with `getPayload()->getBoolean()`.
- **Rationale**: `json_login` rejects a non-string email or password with `BadRequestHttpException` before checking credentials; FR-007 asks for the same treatment, so the check must run at the same point. Symfony's security events documentation describes `CheckPassportEvent` and per-firewall dispatchers. The exception is not an `AuthenticationException`, so it bypasses the failure handler and reaches `kernel.exception`, where the existing `HttpExceptionErrorMapper` answers 400 `http_error`.
- **Alternatives considered**: validating in `LoginSuccessHandler` (simplest, but runs after the password check, so wrong credentials would win with 401/403 over the 400); a custom authenticator replacing `json_login` (reimplements what the built-in authenticator does); reading `RememberMeBadge::$parameters` instead of the request (couples to the native remember-me feature the spec rejected).

## 2. Scope of CORS credentials

- **Decision**: A `'^/auth/'` entry with `allow_credentials: true` in `nelmio_cors.paths`, placed before `'^/'`.
- **Rationale**: Nelmio merges path options over `defaults` and uses the first matching path, so origin, methods and headers still come from `defaults`. The scope matches the refresh cookie's `Path=/auth`, so an auth endpoint that reads the cookie needs no CORS change. `allow_origin` is a regex, so Nelmio echoes the concrete request origin, never `*` (FR-002).
- **Alternatives considered**: `allow_credentials` in `defaults` (also authorizes `/api/*`, which uses Bearer tokens and never receives the cookie); an exact `^/auth/(login|refresh|logout)$` regex (a list to maintain that duplicates `security.yaml`).

## 3. How the session mode reaches the service and the cookie

- **Decision**: `RefreshTokenService::issue(User $user, bool $rememberMe)` chooses a 30-day or 24-hour `expiresAt`; `RefreshTokenCookieFactory::create(string $plaintext, ?\DateTimeImmutable $expiresAt)` emits a session cookie (`expire: 0`) when given `null`; `LoginSuccessHandler` passes `$rememberMe ? $pair->refreshTokenExpiresAt : null`.
- **Rationale**: Smallest change: no new types, each layer keeps one rule (lifetime in the service, cookie kind in the HTTP layer). The service stays HTTP-agnostic.
- **Alternatives considered**: a `SessionLifetime` enum (more types for two values the spec treats as final; wins if a third mode appears); letting the service compute the cookie's expiry (puts an HTTP concern in the service).

## 4. Testing time-dependent expiry

- **Decision**: Inject `Psr\Clock\ClockInterface` into `RefreshTokenService` (used in `issue()` and `verify()`) and use `ClockSensitiveTrait::mockTime()` in tests. Require `symfony/clock` explicitly with Composer, since it is only a transitive dependency today.
- **Rationale**: The Symfony Clock component documentation recommends injecting `ClockInterface` for testability and provides `mockTime()` for kernel and web tests. It enables an end-to-end test: login, advance time 24h+1s, refresh is rejected.
- **Alternatives considered**: tolerance-window assertions plus a pre-expired `RefreshToken` fixture (no change to the service, but does not exercise the login → refresh sequence, needs a new Foundry factory, and can be flaky).

## 5. Access token lifetime

- **Decision**: `token_ttl: 900` in `config/packages/lexik_jwt_authentication.yaml`.
- **Rationale**: Closed by the spec. Lexik's Lcobucci provider builds its own `NativeClock`, so the test checks `exp - iat === 900` on the decoded token instead of mocking time.
- **Alternatives considered**: keeping 1 hour and fixing the documentation (rejected in the spec).

## Dependencies and integrations

- **NelmioCorsBundle**: preflight is answered by its `kernel.request` listener before the firewall, so `access_control` does not block `OPTIONS`.
- **NelmioApiDocBundle**: the login request body is an inline `OA\JsonContent` (no DTO), so no `models.names` alias is needed; the 400 transport error stays undocumented per the project's OpenAPI rules.
- **Doctrine**: no schema change; `refresh_tokens.expires_at` already holds the lifetime. Existing sessions keep their 30 days.
