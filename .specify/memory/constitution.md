# SpaceTraders Gateway Constitution

## Core Principles

### I. Idiomatic Symfony, Installed via Flex

- Code MUST follow the Symfony best practices for the version pinned in `composer.json`
  (Symfony 8.1, PHP >= 8.5).
- Framework metadata MUST be declared with PHP attributes (`#[Route]`, `#[MapRequestPayload]`,
  `#[MapQueryString]`, `#[IsGranted]`, `#[Assert\...]`, `#[AsCommand]`, `#[AsEventListener]`,
  `#[Autoconfigure]`, `#[AsAlias]`, `#[Autowire]`, `#[Target]`). YAML/XML routing is forbidden;
  a YAML service definition is allowed only when no attribute can express the wiring.
- Services MUST rely on autowiring and autoconfiguration with constructor property promotion.
- New capabilities MUST be added with `composer require` so the Flex recipe registers the bundle
  and its base config. `config/bundles.php` and recipe-owned config MUST NOT be hand-written.
- Before hand-writing infrastructure (locks, queues, caches, HTTP clients, mailers, schedulers),
  the matching Symfony component MUST be evaluated first; mutual exclusion MUST use
  `symfony/lock`.
- Controllers MUST extend `AbstractController`, stay thin, and delegate to services. Request
  data MUST be bound with `#[MapRequestPayload]` / `#[MapQueryString]`, never `json_decode()`.

Rationale: the framework already solves wiring, validation and serialization; hand-rolled
equivalents drift from the docs and break under upgrades or concurrency.

### II. Uniform API Envelope and Error Contract

- Every JSON response, whether produced by our own endpoints or proxied from SpaceTraders, MUST
  be emitted through `App\Http\EnvelopeResponseFactory` so clients see a single envelope shape.
- Errors MUST be raised as exceptions and translated by an `ExceptionErrorMapperInterface`
  implementation into a stable `App\Http\ApiErrorCode`. Controllers MUST NOT build error
  responses by hand.
- Every endpoint MUST document its success and error shapes in OpenAPI via `#[DataResponse]`,
  `#[ErrorResponse]` and NelmioApiDoc attributes; the generated spec is part of the contract.
- DTOs and value objects MUST be `final readonly` with promoted constructor properties.
  Services that may become lazy MUST NOT be `readonly`.

Rationale: the front end consumes one predictable contract; undocumented or ad-hoc shapes are
breaking changes that nobody notices until a client fails.

### III. SpaceTraders Proxy Boundary

- All calls to the SpaceTraders API MUST go through `App\SpaceTraders\Proxy\GatewayInterface`;
  no other class may talk to SpaceTraders directly.
- Endpoint-specific behaviour MUST be added as a `RouteHookInterface` implementation
  (`beforeForward` to short-circuit, `afterForward` to transform or trigger side effects), not
  by special-casing the proxy controller or `ProxyService`.
- No Symfony HTTP type (`Request`, HttpClient `ResponseInterface`) may cross past
  `HttpResponseEnvelope` into the proxy pipeline.
- SpaceTraders agent tokens MUST be stored encrypted through `AgentTokenCipherInterface` and
  MUST NOT appear in logs, responses or fixtures in plaintext.

Rationale: a single seam keeps the upstream API swappable in tests, keeps per-route logic
isolated and auditable, and protects third-party credentials held on behalf of users.

### IV. Caller-Level Testing (NON-NEGOTIABLE)

- A feature is not done until a test exercises it the way a caller would: an HTTP request for a
  controller (`WebTestCase` + zenstruck/browser `HasBrowser`), a service call for a service
  (`KernelTestCase`). "It did not throw" is not an assertion.
- Test data MUST be built with the Foundry factories in `src/Factory/` plus `#[ResetDatabase]`;
  entities MUST NOT be persisted by hand in tests.
- Tests MUST NOT reach the real SpaceTraders API; they MUST use the fake gateway
  (`tests/Support/UsesFakeSpaceTradersGateway`).
- Every new `ApiErrorCode` and every new error mapper MUST have a test asserting the HTTP status
  and code it produces.

Rationale: caller-level tests verify the contract defined in Principle II and stay valid across
internal refactors.

### V. Static Quality Gates

- Every PHP file MUST declare `declare(strict_types=1);`.
- `composer phpstan` (PHPStan level 9, Symfony/Doctrine/PHPUnit extensions) MUST pass with no new
  baseline entries.
- `composer cs-check` (`@Symfony` php-cs-fixer ruleset) MUST pass.
- `bin/console lint:container` MUST pass, plus `lint:twig templates/` and `lint:yaml config/`
  when the touched files are in scope.

Rationale: strict types and level-9 analysis catch at review time the class of bugs that tests
rarely cover (nullability, array shapes, mis-wired services).

## Technology and Security Constraints

- Runtime: PHP >= 8.5, Symfony 8.1, run inside the Docker stack in `docker/compose.yml`
  (nginx + php-fpm, PostgreSQL). `symfony serve` is not used.
- Interface: JSON API only (Serializer + Validator, documented with NelmioApiDoc). Twig MUST NOT
  render API responses; server-rendered pages require a spec that explicitly introduces them.
- Persistence: Doctrine ORM on PostgreSQL. Schema changes MUST go through migrations
  (`make:migration`, then `doctrine:migrations:migrate`); `doctrine:schema:update` and
  hand-written SQL are forbidden.
- Authentication: SecurityBundle with LexikJWT access tokens and server-side refresh tokens
  delivered in cookies; e-mail verification via `symfonycasts/verify-email-bundle`. New auth
  flows MUST extend this stack rather than introduce a parallel one.
- Access control MUST use Security attributes and voters, not inline checks in controllers.
- `.env` holds defaults only. Real secrets MUST live in `.env.local` or the Symfony secrets vault
  and be read via `%env(...)%`.
- CORS is configured centrally through NelmioCorsBundle; per-controller CORS headers are
  forbidden.

## Development Workflow and Quality Gates

- Features follow the Spec Kit pipeline (specify → plan → tasks → implement). Every plan MUST
  include a Constitution Check that names each principle and states how it is satisfied, or
  justifies the deviation in a Complexity Tracking entry.
- Package installs (composer, npm, editor extensions) are run by the human maintainer. Agents
  MUST hand over the exact commands with a short explanation and a docs link, then verify the
  result by reading the changed files.
- When a maker exists (`bin/console make:*`), it MUST be run with all arguments and
  `--no-interaction`; if it still prompts, the code is written by hand.
- Diagnosis starts from `var/log/dev.log` and the web profiler before code is changed.
- Merge gate: `composer test`, `composer phpstan`, `composer cs-check` and
  `bin/console lint:container` MUST all pass.
- Project documentation and code comments are written in English. Generated explainers under
  `doc/project/` MUST be regenerated when the area they describe changes.
- Commits MUST NOT carry `Co-Authored-By` trailers or any other AI attribution line.

## Governance

- This constitution supersedes conflicting guidance in other project documents. `AGENTS.md`
  remains the runtime development guide and MUST stay consistent with it; when they disagree,
  the conflict MUST be surfaced and resolved by amending one of them, not silently ignored.
- Amendments are made through `/speckit-constitution`, reviewed by the maintainer, and committed
  on their own with the Sync Impact Report removed before commit.
- Versioning follows semantic versioning: MAJOR for removing or redefining a principle, MINOR
  for adding a principle or section or materially expanding guidance, PATCH for clarifications
  and wording.
- Compliance is checked at three points: the Constitution Check in every `plan.md`, the
  `/speckit-analyze` pass before implementation, and code review before merge. Any violation
  MUST be either fixed or recorded with its justification in the plan's Complexity Tracking.

**Version**: 1.0.0 | **Ratified**: 2026-11-04 | **Last Amended**: 2026-11-04
