# API Error Contract — Living Spec

> [DRAFT] Adopted from the code's surface and the project's conventions. Review before trusting.

## Purpose

Gives the front one predictable shape for every success and every error the back produces, with stable codes it can branch on and messages in the reader's language. Without it, each failure would arrive in a different shape and internal details could leak.

## Requirements

### Every response from the back's own endpoints is an envelope
<!-- touches: src/Http/** -->
<!-- adopted: src/Http/EnvelopeResponseFactory.php -->

A success body is `{"data": …}` and an error body is `{"error": {"code", "message"}}`. `code` is one of a fixed list: `authentication_required`, `email_already_registered`, `email_not_verified`, `http_error`, `internal_error`, `invalid_credentials`, `invalid_email_confirmation`, `invalid_refresh_token`, `spacetraders_unreachable`, `validation_failed`. Relayed SpaceTraders errors are the exception and keep SpaceTraders' own shape.

#### Scenario: an error from a back-only route
- **WHEN** any back-only route fails
- **THEN** the body is `{"error": {"code": <one of the list>, "message": …}}`

### Failures answered inside the firewall use the same envelope
<!-- touches: src/EventListener/**, src/Security/Handler/LoginFailureHandler.php, src/Security/Exception/** -->
<!-- adopted: src/EventListener/JwtAuthenticationFailureListener.php -->

Login failures and access-token rejections are decided before any controller runs. They still get the envelope, never the security bundles' own `{"code": 401, "message"}` body.

#### Scenario: missing access token
- **WHEN** a protected route is called with no access token
- **THEN** the body is `{"error": {"code": "authentication_required", …}}`

### An unexpected failure is a 500 that exposes nothing
<!-- touches: src/EventListener/ErrorMapper/ExceptionResponseFactory.php -->
<!-- adopted: src/EventListener/ErrorMapper/ExceptionResponseFactory.php -->

Any failure the back does not recognise gets 500 `internal_error` with the fixed message "An unexpected error occurred.". The real exception message is never shown.

#### Scenario: an unmapped exception
- **WHEN** a route throws an exception nothing maps
- **THEN** the front gets 500 `internal_error` and the original message is absent

### HTTP errors keep their status, message and protocol headers
<!-- touches: src/EventListener/ErrorMapper/HttpExceptionErrorMapper.php -->
<!-- adopted: src/EventListener/ErrorMapper/HttpExceptionErrorMapper.php -->

A plain HTTP error (404, 403, 405, …) gets `http_error` with its own status and message, and keeps headers that belong to that status, such as `Allow` on a 405.

#### Scenario: wrong method
- **WHEN** someone calls `GET /auth/register`
- **THEN** they get 405 `http_error` with an `Allow` header naming POST

### Validation errors are listed per field, in the reader's language
<!-- touches: src/EventListener/ErrorMapper/ValidationFailedExceptionErrorMapper.php, src/Http/ValidationErrorResponseData.php, config/packages/translation.yaml -->
<!-- adopted: src/EventListener/ErrorMapper/ValidationFailedExceptionErrorMapper.php -->

An invalid request body gets 422 `validation_failed` with the fixed message "Validation failed." and `violations`, a map from each field path to all its messages. Messages with no field go under `_global`. The messages follow `Accept-Language` (English or Spanish, anything else falls back to English), and the response varies by `Accept-Language`. `code` and `message` are never translated.

#### Scenario: Spanish requested
- **WHEN** an invalid registration is sent with `Accept-Language: es-ES`
- **THEN** `violations.email` holds the Spanish message, `message` is still "Validation failed.", and `Vary` includes `Accept-Language`

#### Scenario: an unsupported language
- **WHEN** the request asks for `de-DE` or sends no `Accept-Language`
- **THEN** the violation messages are in English

## Uncovered

- Every new error code needs a mapper and a test asserting its status and code. That rule is in the constitution (principle IV), not restated here.
