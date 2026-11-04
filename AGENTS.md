# AGENTS.md

This is a Symfony project (Symfony 8.1, PHP 8.5). Check `composer.json` for the
exact versions and read `symfony.lock` to see which recipes ran.

## Stack

- Persistence: Doctrine ORM, with Doctrine Migrations.
- Interface: a JSON API for a Vue SPA, no server-rendered pages. The back is a
  Backend for Frontend in front of the SpaceTraders API. What the front relies
  on is in [doc/api-contract.md](doc/api-contract.md): read it before changing
  a response, a status code or an error code.
- Auth: SecurityBundle, `json_login` plus LexikJWTAuthenticationBundle.

If a task leaves anything else unspecified, ask rather than guess. If you can't
ask (no interactive channel), state the assumption you're making and pick the
smallest option rather than scaffolding something nobody asked for.

## Adding features: Flex, not hand-wiring

Install new capabilities with `composer require <package>` (e.g. `symfony/lock`,
`symfony/messenger`) and let the Flex recipe register the bundle and generate its
config. Don't hand-edit `config/bundles.php` or hand-write a bundle's base
config; that's what the recipe is for. Don't skip a good-fit component just
because it isn't installed yet; installing it is one command.

## Conventions

Follow https://symfony.com/doc/current/best_practices.html to write idiomatic
Symfony:

- Use PHP attributes for framework metadata, and not only on controllers:
  `#[Route]`, `#[MapRequestPayload]`, `#[IsGranted]` on actions, `#[Assert\...]`
  on properties, `#[AsCommand]`, `#[AsEventListener]`, `#[AsMessageHandler]`, and
  `#[AsAlias]` / `#[AsTaggedItem]` / `#[Autoconfigure]` on services. No YAML or
  XML routing.
- Rely on autowiring and autoconfiguration. Type-hint constructor arguments and
  let the container resolve them. Where a type-hint can't express it, stay in the
  class with `#[Autowire]` (parameters, env vars, expressions) or `#[Target]` (one
  of several implementations of an interface). A YAML service definition is the
  last resort, not the first.
- Controllers extend `AbstractController`, stay thin, and delegate to services.
  They let domain exceptions propagate to the error mappers instead of catching
  them to rethrow an `HttpException`; when one exception needs different
  responses per endpoint, have the service throw distinct exceptions.
- No Symfony HTTP type crosses a layer that doesn't own it: `Request` stops at
  the controller, HttpClient's `ResponseInterface` stops at the gateway.
- Design by SOLID, concretely:
  - Single responsibility: one service per use case; a class that both calls
    SpaceTraders and maps/persists the result gets split.
  - Dependency inversion: depend on interfaces the app owns where there is a
    seam, e.g. `GatewayInterface` (the test fake replaces it), not on
    `HttpClientInterface` or a Doctrine repository directly; bind a single
    implementation with `#[AsAlias]`.
  - Open/closed: add behaviour as a new tagged implementation
    (`#[AutoconfigureTag]` on the interface, `#[AutowireIterator]` on the
    consumer), as `RouteHookInterface` and `ExceptionErrorMapperInterface` do,
    not by growing a `switch`/`if` chain.
  - Interface segregation: small, role-specific interfaces over one wide one.
  - Liskov: implementations honour the interface contract (no narrowed input,
    no exceptions the caller doesn't expect).
- Use the framework for what it already does: Validator for validation,
  Serializer for JSON, Messenger for async work, Security (voters,
  authenticators) for access control.
- Before hand-writing infrastructure (locks, queues, caches, HTTP clients,
  mailers, schedulers) or reaching for a third-party library, check whether a
  Symfony component covers it. It usually does.

Three specifics worth spelling out, because they are easy to get wrong:

- Bind request data with `#[MapRequestPayload]` / `#[MapQueryString]` on action
  arguments, which wires up Serializer and Validator for you, instead of calling
  `json_decode()` or `SerializerInterface` by hand.
- Use constructor property promotion, and `readonly` for DTOs and value objects.
  Don't mark a service `readonly` if it might become `lazy: true`: a lazy proxy
  can't extend a `readonly` class.
- Use `symfony/lock` (`LockFactory`) for mutual exclusion. A hand-built flag or
  lock file looks fine in review and is usually wrong under concurrency.

## Everyday workflow

- The development environment runs in the Docker containers defined in
  `docker/compose.yml`. Your shell already runs inside the php container, so
  `bin/console ...` runs as-is there; `docker` and the Symfony CLI aren't
  available from it. If the containers are stopped, ask the user to start them
  (`./dev up` from the host).
- When something fails, read `var/log/dev.log` and the web profiler
  (`/_profiler`) before changing code.
- Prefer `bin/console make:*` with every argument passed up front and
  `--no-interaction` where supported: makers prompt on a terminal by default,
  which hangs a non-interactive shell. If a maker still needs interactive
  input, hand-write the code instead.
- Schema changes go through migrations (`bin/console make:migration`, then
  `doctrine:migrations:migrate`), never `doctrine:schema:update` or
  hand-written SQL. Ask before running a migration.
- `.env` is committed and holds defaults only. Real secrets belong in `.env.local`
  (git-ignored) or the secrets vault (`bin/console secrets:set`), read via
  `%env(...)%`.

## Testing

Functional/HTTP tests extend `WebTestCase`; service-level tests extend
`KernelTestCase`. Run `composer test`. A feature isn't done until it has a test
that exercises it the way a caller would, an HTTP request for a controller or a
service call for a service, not just "it didn't throw."

- Drive HTTP tests with zenstruck/browser (`HasBrowser`) and build data with the
  Foundry factories in `src/Factory/` plus `#[ResetDatabase]`; don't persist
  entities by hand.
- Reuse the helpers in `tests/Support/`: `AuthenticatesClient` to log in and get
  a JWT, `UsesFakeSpaceTradersGateway` to stage SpaceTraders responses. In the
  test env `GatewayInterface` is bound to `FakeGateway`, so tests never call the
  real API.
- Use a hand-written Fake instead of a PHPUnit mock when several methods must
  agree or state carries between calls: an anonymous class if used once, a
  named `tests/.../Fake*` with `willXxx()` setters once duplicated.
- JSON assertions: `assertJsonMatches()` per field, `assertSame()` on the whole
  `json()->decoded()` when the exact body is the contract, `json()->search()`
  to extract a value. `assertJsonMatches()` compares with `==` (`1` matches
  `"1"`): use `search()` plus `assertSame()` when the type matters.

## Code style

Symfony's coding standard, the `@Symfony` php-cs-fixer ruleset (a PSR-12-derived
superset). Run `composer cs-fix` (or `composer cs-check` to only report).

## Static analysis

PHPStan at level 9 (`phpstan.dist.neon`), run with `composer phpstan`. A change
isn't done until it reports no errors. Fix the cause; don't add
`@phpstan-ignore` or `ignoreErrors` entries without asking first. If it
complains about unknown services, regenerate the dev container
(`bin/console cache:warmup`) before changing code.

## Discover, don't guess

Framework APIs change between versions and your training data may be stale. Look
things up in the project instead of relying on memory:

- `bin/console about`: versions, environment, paths.
- `bin/console debug:router`, `debug:container`, `debug:autowiring <name>`,
  `debug:config <bundle>`, `config:dump-reference <bundle>`: what exists and how
  it is configured.
- `bin/console lint:container`, `lint:twig templates/` and `lint:yaml config/`:
  validate before running.
- Read the installed source and docblocks under `vendor/`.
- Docs: https://symfony.com/doc/current/ (switch to the version matching
  `composer.json` if it differs).
