# Feature Specification: User Login

**Feature Branch**: `speckit-companion`
**Created**: 2026-11-04
**Status**: Draft (retroactive)
**Input**: "Quiero implementar el acceso de un usuario registrado. Hay código hecho, revisar lo que hay hecho y lo que es necesario hacer."

This spec documents sign-in behaviour that already exists and is covered by passing tests. The review on 2026-11-04 found no missing behaviour against the living spec `session-access`, and the user chose to document what is built rather than add to it. No new code is in scope.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Sign in with email and password (Priority: P1)

A person who registered and confirmed their email signs in with their email and password. They get a short-lived access token for calling the API, and the browser keeps a refresh cookie that the front cannot read.

**Why this priority**: without it no registered person can use anything behind the login.

**Independent Test**: register and verify an account, sign in, and call a protected route with the returned token.

**Acceptance Scenarios**:

1. **Given** a verified account, **When** the person signs in with the right email and password, **Then** they get 200 with an access token valid for 15 minutes, and the refresh token arrives only as a cookie.
2. **Given** any account, **When** someone signs in with the wrong password, **Then** they get 401 `invalid_credentials`.
3. **Given** no account for an email, **When** someone signs in with it, **Then** they get the same 401 `invalid_credentials` a wrong password gets.
4. **Given** an unverified account, **When** someone signs in with the right password, **Then** they get 403 `email_not_verified`; with the wrong password they get 401 `invalid_credentials`.
5. **Given** a login request whose body is not JSON, **When** it is sent, **Then** it gets 415.

### User Story 2 - Choose how long the session lasts (Priority: P2)

At sign-in the person says whether to stay signed in. "Remember me" keeps them in for 30 days, even after closing the browser. Otherwise the session ends when the browser closes and at the latest 24 hours after sign-in.

**Why this priority**: it decides whether people on shared machines stay exposed, but signing in works without it.

**Independent Test**: sign in with and without remember me, then refresh just before and just after each limit.

**Acceptance Scenarios**:

1. **Given** a sign-in with remember me, **When** the cookie is inspected, **Then** it expires 30 days after sign-in and refreshes work until then.
2. **Given** a sign-in without remember me, or with it set to false, **When** the cookie is inspected, **Then** it has no expiry, and a refresh one second after 24 hours is rejected.
3. **Given** a remember me value that is not true or false, **When** the person signs in, **Then** they get 400 `http_error`, even if the password is wrong or the account unverified.

### User Story 3 - Stay signed in and sign out (Priority: P2)

While the session lives, the front exchanges the refresh cookie for a new access token. Signing out ends the current session only, so the person's other devices stay signed in.

**Why this priority**: without refresh, people would sign in again every 15 minutes.

**Independent Test**: sign in twice, refresh from both, sign out from one, and refresh again from both.

**Acceptance Scenarios**:

1. **Given** a live session, **When** the front refreshes, **Then** it gets a new 15-minute access token, no new cookie is set, and the session still ends when it was due at sign-in.
2. **Given** no cookie, or one that is unknown, expired or revoked, **When** the front refreshes, **Then** it gets 401 `invalid_refresh_token` and the cookie is cleared.
3. **Given** a person signed in on two devices, **When** they sign out on one, **Then** that session can no longer refresh and the other one still can.
4. **Given** no cookie or an unknown one, **When** someone signs out, **Then** they still get 204.
5. **Given** a person who already has a session, **When** they sign in again, **Then** they get a new session and the old one keeps working until it expires.

### User Story 4 - Protected routes refuse anonymous callers (Priority: P1)

Everything except sign-up, sign-in and the API docs needs a valid access token. A caller without one gets the same answer whatever the reason.

**Why this priority**: it is what makes signing in meaningful; without it the data is open.

**Independent Test**: call a protected route with no token, a malformed one, an expired one and one whose user was deleted.

**Acceptance Scenarios**:

1. **Given** a protected route, **When** it is called with a missing, malformed or expired token, **Then** it answers 401 `authentication_required` with `WWW-Authenticate: Bearer`.
2. **Given** a valid token whose user was deleted, **When** it is used, **Then** the answer is the same 401 `authentication_required`, not `invalid_credentials`.

### Edge Cases

- An unverified account must not be revealed to someone without its password: the password is checked first.
- A malformed remember me is refused before the credentials are checked, so it never says whether the password was right.
- Signing in while sending an old refresh cookie does not revoke the old session.
- Refreshing never extends a session, so a session always ends at the time fixed at sign-in.
- Signing out twice, or without a cookie, is harmless.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A verified person MUST be able to sign in with email and password and receive an access token that expires 15 minutes after it is issued.
- **FR-002**: The refresh token MUST be delivered only in a cookie the front cannot read, sent only over HTTPS, never cross-site, and only to the sign-in routes. It MUST NOT appear in the response body.
- **FR-003**: With remember me, the session MUST last 30 days from sign-in in a persistent cookie. Without it, the cookie MUST end with the browser and the server MUST stop accepting it 24 hours after sign-in.
- **FR-004**: A remember me value that is present but not true or false MUST be refused with 400 `http_error`, before the credentials are checked.
- **FR-005**: A wrong password and an unknown email MUST get the same 401 `invalid_credentials` answer.
- **FR-006**: An unverified account MUST get 403 `email_not_verified`, and only when the password is right.
- **FR-007**: A login request whose body is not JSON MUST get 415.
- **FR-008**: A refresh with a live cookie MUST return a new 15-minute access token without setting a new cookie or extending the session.
- **FR-009**: A refresh with a missing, unknown, expired or revoked cookie MUST get 401 `invalid_refresh_token` and clear the cookie.
- **FR-010**: Signing out MUST end only the current session, clear the cookie and answer 204, with or without a valid cookie.
- **FR-011**: Each sign-in MUST start a new session and leave any existing session working.
- **FR-012**: Every route except sign-up, sign-in, refresh, sign-out, email confirmation and the API docs MUST require a valid access token, and MUST refuse any other caller with the same 401 `authentication_required` and `WWW-Authenticate: Bearer`.
- **FR-013**: Every error above MUST use the API's standard error envelope, including those decided before any controller runs.

### Key Entities

- **User**: a registered person, identified by email, with a password and a verified flag. Only verified users can sign in.
- **Session (refresh token)**: one signed-in device. Belongs to one user, has an expiry fixed at sign-in, and can be revoked. Stored only as a hash.
- **Access token**: a short-lived proof of identity sent with each API call. Not stored.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every functional requirement above is exercised by at least one passing automated test (13 of 13).
- **SC-002**: The sign-in, refresh, sign-out and protected-route test suites pass with 0 failures.
- **SC-003**: The answers to an unknown email and to a wrong password are identical in status, code and message.
- **SC-004**: This spec and the living spec `session-access` contradict each other in 0 places.

## Assumptions

- "Access of a registered user" means the whole sign-in session: sign in, stay signed in, sign out, and protected routes. Registration and email confirmation belong to `account-registration` and are out of scope.
- Brute-force protection on login, a "current user" endpoint and "sign out everywhere" are not built and stay out of scope. They were offered and not chosen.
- Refresh tokens do not rotate. This trade-off is already accepted in the code: a stolen refresh token stays valid until it expires or its session signs out.

## Approach

No code changes. The behaviour already lives in:

- `src/Controller/Auth/LoginController.php`, `RefreshController.php`, `LogoutController.php`
- `src/Security/Handler/LoginSuccessHandler.php`, `LoginFailureHandler.php`, `src/Security/Checker/UserVerificationChecker.php`, `src/Security/EventListener/RememberMeChoiceListener.php`
- `src/Security/RefreshTokenService.php`, `RefreshTokenCookieFactory.php`, `src/Entity/RefreshToken.php`
- `config/packages/security.yaml` (firewalls and public routes), `config/packages/lexik_jwt_authentication.yaml` (15-minute token)
- `src/EventListener/JwtAuthenticationFailureListener.php` (envelope for rejected tokens)

Tests: `tests/Controller/Auth/LoginControllerTest.php`, `RefreshControllerTest.php`, `LogoutControllerTest.php`, `tests/Controller/AuthenticationRequiredTest.php`, `tests/Security/RefreshTokenServiceTest.php`. The work is to confirm each requirement maps to one of them.
