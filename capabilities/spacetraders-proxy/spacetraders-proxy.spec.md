# SpaceTraders Proxy — Living Spec

> [DRAFT] Adopted from the code's surface and the project's conventions. Review before trusting.

## Purpose

Lets the front call any SpaceTraders endpoint through this back, acting as one of the person's agents, without ever holding the agent's token itself. Without it the front would need the SpaceTraders tokens, or could act as an agent it does not own.

## Requirements

### Any SpaceTraders path is reachable under /api/spacetraders
<!-- touches: src/Controller/SpaceTraders/**, src/SpaceTraders/Proxy/**, src/SpaceTraders/RouteHookInterface.php -->
<!-- adopted: src/Controller/SpaceTraders/SpaceTradersProxyController.php -->
<!-- aligns: session-access#Everything outside sign-up, sign-in and the docs requires a valid access token -->

`/api/spacetraders/{path}` with GET, POST, PUT, PATCH or DELETE forwards to `https://api.spacetraders.io/v2/{path}`, with the query string, the body and the `Content-Type` header. No other request header is forwarded. The bare `/api/spacetraders` reaches the API root (server status).

#### Scenario: server status
- **WHEN** a signed-in person calls `GET /api/spacetraders`
- **THEN** the call reaches the SpaceTraders root and they get its status body

### Acting as an agent needs an agent the caller owns
<!-- touches: src/SpaceTraders/Proxy/ProxyService.php, src/Repository/AgentRepository.php -->
<!-- adopted: src/SpaceTraders/Proxy/ProxyService.php -->

With an `X-Agent-Id` header, the call goes to SpaceTraders as that agent, using the agent's stored token in place of the caller's access token. An unknown id gets 404 and another person's agent gets 403, and in both cases SpaceTraders is never called. Without the header, no `Authorization` is sent upstream.

#### Scenario: someone else's agent
- **WHEN** a person sends `X-Agent-Id` for an agent owned by another person
- **THEN** they get 403 `http_error` and SpaceTraders is not called

#### Scenario: unknown agent
- **WHEN** `X-Agent-Id` names an agent that does not exist
- **THEN** they get 404 `http_error`

#### Scenario: own agent
- **WHEN** a person sends `X-Agent-Id` for their own agent
- **THEN** SpaceTraders receives `Authorization: Bearer <that agent's token>`

### A proxied response carries SpaceTraders' own status and body
<!-- touches: src/SpaceTraders/Proxy/Gateway.php, src/SpaceTraders/Response/HttpEnvelopeMerger.php -->
<!-- adopted: src/SpaceTraders/Proxy/Gateway.php -->

SpaceTraders' status and body reach the front untouched, errors included: a SpaceTraders 4xx or 5xx is relayed as-is and never turned into this back's error envelope. SpaceTraders' response headers are not relayed. [NEEDS CLARIFICATION: is dropping SpaceTraders' response headers (e.g. rate-limit headers) intended? The developer did not know on 2026-11-04.]

#### Scenario: SpaceTraders rejects the call
- **WHEN** SpaceTraders answers 4xx with its own error body
- **THEN** the front gets that status and that body verbatim

### An unreachable SpaceTraders is a 502
<!-- touches: src/SpaceTraders/Proxy/Gateway.php, src/SpaceTraders/Exception/**, src/EventListener/ErrorMapper/SpaceTradersApiExceptionErrorMapper.php -->
<!-- adopted: src/EventListener/ErrorMapper/SpaceTradersApiExceptionErrorMapper.php -->

A transport failure (timeout, DNS, connection refused) gets 502 `spacetraders_unreachable` with a fixed message, not a 500.

#### Scenario: SpaceTraders times out
- **WHEN** the call to SpaceTraders fails at the transport level
- **THEN** the front gets 502 `spacetraders_unreachable`

### Fields the back adds never replace SpaceTraders' own
<!-- touches: src/SpaceTraders/Response/** -->
<!-- adopted: src/SpaceTraders/Response/HttpEnvelopeMerger.php -->

When the back adds top-level fields to a proxied response (such as `agentId` on registration), they sit beside SpaceTraders' keys. A field that would overwrite a SpaceTraders key, or a SpaceTraders body that is not a JSON object, fails the request instead of silently picking a winner.

#### Scenario: an added field collides
- **WHEN** an added field has the same name as a top-level key in SpaceTraders' body
- **THEN** the request fails with 500 `internal_error` instead of returning either value

#### Scenario: nothing to add
- **WHEN** the back adds no fields
- **THEN** SpaceTraders' body is relayed byte for byte, without being decoded and re-encoded

### Agent tokens are stored encrypted
<!-- touches: src/Security/AgentTokenCipherInterface.php, src/Security/SodiumAgentTokenCipher.php, src/Security/Exception/InvalidCiphertextException.php -->
<!-- adopted: src/Security/SodiumAgentTokenCipher.php -->

SpaceTraders tokens (account and agent) are kept encrypted with `TOKEN_ENCRYPTION_KEY`, which must decode from base64 to 32 bytes. A tampered stored token cannot be decrypted and is never used.

#### Scenario: a wrong-length key
- **WHEN** `TOKEN_ENCRYPTION_KEY` does not decode to 32 bytes
- **THEN** the application refuses to build the cipher

#### Scenario: a tampered ciphertext
- **WHEN** a stored token's ciphertext has been altered
- **THEN** decrypting it fails instead of returning a token

## Uncovered

- Per-agent rate limiting against SpaceTraders: the gateway is named as the place to add it, but none exists.
- Agent registration and listing (`src/Controller/Agent/**`, `src/Agent/**`, `src/SpaceTraders/Hook/**`, `src/Entity/Agent.php`) belong to no capability. The developer removed `agent-roster` on 2026-11-04 because the feature is not finished, although some code exists. Open points for when it is specified: registration does not yet send the SpaceTraders account token, and the raw agent token reaches the front in plaintext, which contradicts constitution principle III.
