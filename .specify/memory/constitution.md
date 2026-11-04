# SpaceTraders BFF Constitution

## Core Principles

### I. Front Contract Stability

The back is a Backend for Frontend in front of the SpaceTraders API, consumed only
by the Vue SPA. What the front relies on is the contract in `doc/api-contract.md`
plus the generated OpenAPI spec (`/api/doc`).

- Any change to a response shape, status code or error code MUST be checked against
  `doc/api-contract.md` first and treated as a breaking change for the front.
- Back-only endpoints MUST answer `{"data": ...}` on success and
  `{"error": {"code", "message"}}` on failure, with `code` taken from `ApiErrorCode`.
- Proxied endpoints (`/api/spacetraders/{path}`) MUST relay SpaceTraders' status code
  and MUST NOT remove, rename or override its top-level `data` / `error` keys.
- A change that alters a guarantee MUST update `doc/api-contract.md` in the same
  change.

Rationale: the front's e2e tests mock this contract; silent drift breaks it without
any back test failing.

### II. Framework-First, Idiomatic Symfony

Code MUST follow the Symfony best practices for the versions in `composer.json`.

- Framework metadata MUST be declared with PHP attributes (`#[Route]`,
  `#[MapRequestPayload]`, `#[IsGranted]`, `#[Assert\...]`, `#[AsAlias]`, ...). No YAML
  or XML routing; a YAML service definition is a last resort.
- Services MUST rely on autowiring and autoconfiguration, using `#[Autowire]` or
  `#[Target]` where a type-hint is not enough.
- New capabilities MUST be added with `composer require` and their Flex recipe, not by
  hand-editing `config/bundles.php` or hand-writing a bundle's base config.
- Before hand-writing infrastructure (locks, queues, caches, HTTP clients, mailers,
  schedulers) or adding a third-party library, an existing Symfony component MUST be
  considered first. Mutual exclusion MUST use `symfony/lock`.
- Request data MUST be bound with `#[MapRequestPayload]` / `#[MapQueryString]`, not
  `json_decode()` or a hand-called serializer.

Rationale: the framework already solves these problems correctly under concurrency
and keeps the codebase predictable for anyone who knows Symfony.

### III. Owned Seams and Layer Boundaries

- Controllers MUST extend `AbstractController`, stay thin, delegate to one service per
  use case, and let domain exceptions propagate to the error mappers.
- No Symfony HTTP type crosses a layer it does not belong to: `Request` stops at the
  controller, HttpClient's `ResponseInterface` stops at the gateway.
- Where there is a seam, code MUST depend on an interface the app owns (e.g.
  `GatewayInterface`), not on `HttpClientInterface` or a Doctrine repository directly.
- New behaviour on an extension point MUST be added as a new tagged implementation
  (`#[AutoconfigureTag]` + `#[AutowireIterator]`, as `RouteHookInterface` and
  `ExceptionErrorMapperInterface` do), not by growing a `switch`/`if` chain.
- DTOs and value objects MUST use constructor promotion and `readonly`; services that
  may become lazy MUST NOT be `readonly`.

Rationale: owned seams make the SpaceTraders dependency replaceable in tests and keep
each class to a single, reviewable responsibility.

### IV. Caller-Level Testing (NON-NEGOTIABLE)

A feature is not done until a test exercises it the way a caller would: an HTTP
request for a controller, a service call for a service.

- HTTP tests MUST extend `WebTestCase` and use zenstruck/browser; service tests MUST
  extend `KernelTestCase`.
- Test data MUST be built with the Foundry factories in `src/Factory/` and
  `#[ResetDatabase]`, not persisted by hand.
- Tests MUST NOT call the real SpaceTraders API; they stage responses through
  `FakeGateway` (`UsesFakeSpaceTradersGateway`) and reuse the helpers in
  `tests/Support/`.
- Where the JSON body is the contract, the test MUST assert it exactly; where a type
  matters, it MUST NOT rely on the loose `==` of `assertJsonMatches()`.

Rationale: "it didn't throw" does not prove the contract in Principle I holds.

### V. Static Quality Gates

- PHPStan at level 9 (`phpstan.dist.neon`) MUST report no errors. New
  `@phpstan-ignore` or `ignoreErrors` entries require explicit approval from the
  maintainer; the default is to fix the cause.
- Code MUST pass the `@Symfony` php-cs-fixer ruleset (`composer cs-check`).

Rationale: level 9 typing catches the mismatches between SpaceTraders payloads and
the app's own types that tests easily miss.

## Technology and Security Constraints

- Stack: Symfony and PHP at the versions pinned in `composer.json`; Doctrine ORM with
  Doctrine Migrations; JSON API only, no server-rendered pages.
- Authentication: SecurityBundle `json_login` plus LexikJWTAuthenticationBundle. Access
  control MUST use Security (voters, authenticators, `#[IsGranted]`).
- The front's `Authorization` header MUST NOT be forwarded to SpaceTraders; the back
  uses the selected agent's token. A user MUST NOT act through another user's agent
  (`403`), and an unknown agent is a `404`.
- Schema changes MUST go through migrations (`make:migration`, then
  `doctrine:migrations:migrate`), never `doctrine:schema:update` or hand-written SQL.
- `.env` holds defaults only. Secrets MUST live in `.env.local` or the Symfony secrets
  vault and be read via `%env(...)%`.

## Development Workflow and Quality Gates

- The development environment runs in the Docker containers in `docker/compose.yml`.
- Before merging, a change MUST pass `composer test`, `composer phpstan` and
  `composer cs-check`.
- Framework APIs MUST be verified in the project (`bin/console debug:*`,
  `config:dump-reference`, `vendor/` sources, the docs for the pinned version) rather
  than assumed from memory.
- When requirements are unspecified, the open question MUST be raised; if that is not
  possible, the assumption MUST be stated and the smallest option chosen.
- Project documentation is written in English.

## Governance

This constitution takes precedence over other practice documents. `AGENTS.md` holds
the detailed, day-to-day guidance that implements it; where the two disagree, the
disagreement MUST be raised and one of them amended rather than silently picking one.

- Amendments are made through `/speckit-constitution`, reviewed as a normal change, and
  MUST update `AGENTS.md` or `doc/api-contract.md` when they change rules stated
  there.
- Versioning follows semantic versioning: MAJOR for removing or redefining a
  principle, MINOR for a new principle or section or materially expanded guidance,
  PATCH for clarifications and wording.
- Every spec, plan and review MUST check compliance with these principles; any
  deviation MUST be justified in the plan's complexity tracking.

**Version**: 1.0.0 | **Ratified**: 2026-11-04 | **Last Amended**: 2026-11-04
