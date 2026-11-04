# Cross-Origin Access — Living Spec

> [DRAFT] Adopted from the code's surface and the project's conventions. Review before trusting.

## Purpose

The front runs on a different origin from the back. This capability lets the front's browser call the API and carry the refresh cookie only where it is needed. Without it, the browser blocks every call, or any origin could use a person's session.

## Requirements

### Only allowed origins may call the API, with credentials only under /auth
<!-- touches: config/packages/nelmio_cors.yaml -->
<!-- adopted: config/packages/nelmio_cors.yaml -->

Origins matching `CORS_ALLOW_ORIGIN` get CORS headers. Under `/auth/` credentials are allowed, because the refresh cookie lives at `Path=/auth`. Elsewhere the origin is allowed without credentials. A disallowed origin gets no CORS headers at all.

#### Scenario: preflight outside /auth
- **WHEN** an allowed origin preflights `/api/agents`
- **THEN** the origin is allowed and credentials are not

#### Scenario: disallowed origin
- **WHEN** an origin outside `CORS_ALLOW_ORIGIN` calls or preflights any route
- **THEN** the response carries no CORS headers

### The front can send the headers the API needs
<!-- touches: config/packages/nelmio_cors.yaml -->
<!-- adopted: config/packages/nelmio_cors.yaml -->

A cross-origin request may send `Content-Type`, `Authorization` and `X-Agent-Id`. [NEEDS CLARIFICATION: defect. Today `allow_headers` lists only `Content-Type` and `Authorization`, so the browser's preflight blocks every proxied call that acts as an agent. The developer confirmed on 2026-11-04 that the front is cross-origin.]

#### Scenario: acting as an agent from the front
- **WHEN** the front at an allowed origin preflights `/api/spacetraders/my/ships` with `X-Agent-Id`
- **THEN** the preflight allows `X-Agent-Id`
