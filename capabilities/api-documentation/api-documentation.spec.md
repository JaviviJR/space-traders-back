# API Documentation — Living Spec

> [DRAFT] Adopted from the code's surface and the project's conventions. Review before trusting.

## Purpose

Publishes the OpenAPI document the front generates its client from. Without it, a change to a route, a payload or an error code would break the front without anyone noticing.

## Requirements

### The OpenAPI document is public
<!-- touches: config/routes/nelmio_api_doc.yaml, config/packages/security.yaml -->
<!-- adopted: config/routes/nelmio_api_doc.yaml -->

`GET /api/doc.json` serves the document and `GET /api/doc` serves its UI, both without an access token. The document covers every route under `/auth` and `/api`, except the docs themselves and the proxy catch-all.

#### Scenario: anonymous fetch
- **WHEN** someone fetches `/api/doc.json` without an access token
- **THEN** they get the OpenAPI document

### Error codes and error shapes are documented exactly
<!-- touches: src/OpenApi/**, src/Http/ApiErrorCode.php, config/packages/nelmio_api_doc.yaml -->
<!-- adopted: src/OpenApi/Describer/AuthenticationErrorResponseDescriber.php -->

Every error code is listed as a string enum. Each documented error response narrows `code` to the one value it can return. Every operation that needs an access token documents the 401 `authentication_required`, and public ones do not. Every operation with a validated request body documents its 422 `validation_failed`, except `confirmEmail`, which never returns one.

#### Scenario: a JWT-protected operation
- **WHEN** the document describes `listAgents`
- **THEN** it includes a 401 response whose `code` is `authentication_required`

#### Scenario: a public operation
- **WHEN** the document describes `login`
- **THEN** it has no `authentication_required` response

### A representative set of proxied routes is documented from SpaceTraders' own spec
<!-- touches: src/SpaceTraders/OpenApi/**, resources/spacetraders/openapi.json -->
<!-- adopted: src/SpaceTraders/OpenApi/ProxyDescriber.php -->

`GET /api/spacetraders`, `POST /api/spacetraders/register` and `GET /api/spacetraders/my/ships` are documented with schemas copied from the vendored SpaceTraders spec, never hand-written. Each requires this back's access token, not a SpaceTraders token. Their operation ids are prefixed `spacetraders_`. Fields the back adds, such as `agentId`, appear beside SpaceTraders' `data`.

#### Scenario: agent registration
- **WHEN** the document describes `POST /api/spacetraders/register`
- **THEN** its success schema has SpaceTraders' `data` plus an integer `agentId`, and its security is the back's bearer token

### Schema names are the ones the front's codegen expects
<!-- touches: config/packages/nelmio_api_doc.yaml -->
<!-- adopted: config/packages/nelmio_api_doc.yaml -->

Request and response models appear under front-facing names (`RegisterUserRequest`, `AccessTokenResponse`, `ApiError`, `ApiErrorValidation`, …), never as their PHP class names. A rename is a breaking change for the front. [inferred]

#### Scenario: register request body
- **WHEN** the document describes `registerUser`
- **THEN** its request body references `RegisterUserRequest`, and no `RegisterUserDto` schema exists

## Uncovered

- `ListAgentsResponseItem` is a provisional name, marked TODO in `config/packages/nelmio_api_doc.yaml`.
