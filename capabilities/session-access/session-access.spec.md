# Session Access — Living Spec

> [DRAFT] Adopted from the code's surface and the project's conventions. Review before trusting.

## Purpose

Lets a verified person sign in, stay signed in for the time they chose, and sign out. It also keeps every other route closed to anyone without a valid access token. Without it, sessions would outlive what the person asked for, or protected data would be reachable anonymously.

## Requirements

### Signing in returns an access token and sets a refresh cookie
<!-- touches: src/Controller/Auth/LoginController.php, src/Security/Handler/LoginSuccessHandler.php, src/Security/RefreshTokenService.php, src/Security/RefreshTokenCookieFactory.php, config/packages/security.yaml, config/packages/lexik_jwt_authentication.yaml, src/Security/Dto/AccessTokenResponseData.php, src/Security/Dto/RefreshTokenPair.php -->
<!-- adopted: src/Security/Handler/LoginSuccessHandler.php -->

`POST /auth/login` with a JSON email and password answers 200 with an access token that expires 15 minutes after it is issued. The refresh token is never in the body. It comes in a `refresh_token` cookie that is httpOnly, Secure, SameSite=Strict and scoped to `/auth`.

#### Scenario: correct credentials for a verified account
- **WHEN** a verified person signs in with the right email and password
- **THEN** they get 200 with an access token valid for 15 minutes, and the refresh token arrives only as the cookie

#### Scenario: a body that is not JSON
- **WHEN** the login request body is not JSON
- **THEN** they get 415

### Remember me decides how long the session lasts
<!-- touches: src/Security/Handler/LoginSuccessHandler.php, src/Security/RefreshTokenService.php, src/Security/RefreshTokenCookieFactory.php, src/Security/EventListener/RememberMeChoiceListener.php -->
<!-- adopted: src/Security/RefreshTokenService.php -->

With `rememberMe: true` the session lasts 30 days from sign-in, in a persistent cookie. Without it, or with `false`, the cookie is a session cookie and the server stops accepting it 24 hours after sign-in. The expiry is fixed at sign-in and refreshing never extends it.

#### Scenario: remember me
- **WHEN** someone signs in with `rememberMe: true`
- **THEN** the cookie expires 30 days after sign-in and refreshes work until then

#### Scenario: no remember me
- **WHEN** someone signs in without `rememberMe`
- **THEN** the cookie has no expiry, and a refresh one second after 24 hours is rejected

### A malformed remember-me choice is rejected before the credentials are checked
<!-- touches: src/Security/EventListener/RememberMeChoiceListener.php -->
<!-- adopted: src/Security/EventListener/RememberMeChoiceListener.php -->

A `rememberMe` that is present but not a boolean (text, number or an explicit null) gets 400 `http_error` and starts no session. That answer wins over a wrong password and over an unverified account, the same way a non-string email or password does.

#### Scenario: malformed remember me with a wrong password
- **WHEN** someone sends `rememberMe: "yes"` with a wrong password
- **THEN** they get 400 `http_error`, not 401, and no cookie is set

### Wrong credentials and unverified accounts are refused without leaking account state
<!-- touches: src/Security/Handler/LoginFailureHandler.php, src/Security/Checker/UserVerificationChecker.php, src/Security/Exception/InvalidLoginCredentialsException.php, src/Security/Exception/EmailNotVerifiedException.php, src/EventListener/ErrorMapper/InvalidLoginCredentialsExceptionErrorMapper.php, src/EventListener/ErrorMapper/EmailNotVerifiedExceptionErrorMapper.php -->
<!-- adopted: src/Security/Checker/UserVerificationChecker.php -->

A wrong password and an unknown email get the same 401 `invalid_credentials`. An unverified account gets 403 `email_not_verified`, but only once the password is right. Someone without the password cannot learn that the account exists or is unverified.

#### Scenario: unknown email
- **WHEN** someone signs in with an email that has no account
- **THEN** they get the same 401 `invalid_credentials` a wrong password gets

#### Scenario: unverified account with the wrong password
- **WHEN** someone signs in to an unverified account with the wrong password
- **THEN** they get 401 `invalid_credentials`, not 403

#### Scenario: unverified account with the right password
- **WHEN** someone signs in to an unverified account with the right password
- **THEN** they get 403 `email_not_verified`

### Refreshing issues a new access token while the session is alive
<!-- touches: src/Controller/Auth/RefreshController.php, src/Security/RefreshTokenService.php, src/Security/Exception/InvalidRefreshTokenException.php, src/EventListener/ErrorMapper/InvalidRefreshTokenExceptionErrorMapper.php, src/Entity/RefreshToken.php, src/Repository/RefreshTokenRepository.php -->
<!-- adopted: src/Controller/Auth/RefreshController.php -->

`POST /auth/refresh` with a live refresh cookie answers 200 with a new 15-minute access token. It neither resets the cookie nor rotates the refresh token. A missing, unknown, expired or revoked cookie gets 401 `invalid_refresh_token`, and the response clears the cookie.

#### Scenario: no cookie
- **WHEN** someone refreshes without a refresh cookie
- **THEN** they get 401 `invalid_refresh_token` and a Set-Cookie that clears it

#### Scenario: refreshing does not extend the session
- **WHEN** someone refreshes successfully
- **THEN** no new cookie is set and the session still ends when it was due at sign-in

### Signing out ends only the current session and always succeeds
<!-- touches: src/Controller/Auth/LogoutController.php, src/Security/RefreshTokenService.php, src/Security/RefreshTokenCookieFactory.php -->
<!-- adopted: src/Controller/Auth/LogoutController.php -->

`POST /auth/logout` revokes the refresh token in the cookie, if any, clears the cookie and answers 204. Other sessions of the same person keep working.

#### Scenario: two sessions
- **WHEN** a person signed in twice signs out from one session
- **THEN** that session's refresh is rejected and the other one still refreshes

#### Scenario: no cookie or an unknown one
- **WHEN** someone signs out without a cookie or with an unknown one
- **THEN** they still get 204

### Signing in again starts a new session and leaves the old one alone
<!-- touches: src/Security/Handler/LoginSuccessHandler.php, src/Security/RefreshTokenService.php -->
<!-- adopted: src/Security/RefreshTokenService.php -->

Each sign-in issues a new refresh token. A refresh cookie already present is not revoked.

#### Scenario: sign-in with an old cookie
- **WHEN** someone signs in while sending a previous refresh cookie
- **THEN** they get a new session, and the previous refresh token still refreshes until it expires

### Everything outside sign-up, sign-in and the docs requires a valid access token
<!-- touches: config/packages/security.yaml, src/EventListener/JwtAuthenticationFailureListener.php, src/Security/Exception/AccessTokenRejectedException.php, src/EventListener/ErrorMapper/AccessTokenRejectedExceptionErrorMapper.php -->
<!-- adopted: config/packages/security.yaml -->

Only `/auth/{register,login,refresh,logout,confirm-email,resend-confirmation}` and `/api/doc` are public. Every other route needs a bearer access token. A missing, malformed or expired token, or one whose user was deleted, gets 401 `authentication_required` with `WWW-Authenticate: Bearer` and a fixed message that does not say which case it was.

#### Scenario: expired token on a protected route
- **WHEN** someone calls `/api/agents` or a proxied route with an expired access token
- **THEN** they get 401 `authentication_required` with `WWW-Authenticate: Bearer`

#### Scenario: token for a deleted user
- **WHEN** the access token is valid but its user no longer exists
- **THEN** the response is the same 401 `authentication_required`, not `invalid_credentials`

## Uncovered

- Refresh tokens do not rotate and nothing detects reuse. A stolen refresh token stays valid until it expires or its session signs out. This is an accepted trade-off, documented in `src/Security/RefreshTokenService.php`.
- `RefreshTokenService::revokeAllForUser()` exists and is tested, but no route uses it ("sign out everywhere" is not offered).
