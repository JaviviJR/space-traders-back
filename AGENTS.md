# AGENTS.md

This is a Symfony project. Check `composer.json` for the exact Symfony/PHP version
in use, and read `symfony.lock` to see which recipes ran. Don't assume Doctrine,
Twig, API Platform, Messenger, or Lock are installed unless one of those says so.

## Ask before generating

If the task doesn't specify, ask rather than guess:

- Persistence: Doctrine ORM, Doctrine ODM, or none?
- Interface: server-rendered (Twig), API (Serializer, maybe API Platform), or both?
- Auth: SecurityBundle, and which authenticator?

If you can't ask (no interactive channel), state the assumption you're making and
pick the smallest option (e.g. no persistence layer) rather than scaffolding a
full stack nobody asked for.

## Adding features: Flex, not hand-wiring

Install new capabilities with `composer require <package>` (e.g. `symfony/lock`,
`symfony/messenger`, `orm-pack`) and let the Flex recipe register the bundle and
generate its config. Don't hand-edit `config/bundles.php` or hand-write a bundle's
base config; that's what the recipe is for. Don't skip a good-fit component just
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
- Use the framework for what it already does: Form for server-rendered forms,
  Validator for validation, Serializer for JSON, Messenger for async work,
  Security (voters, authenticators) for access control, Twig `path()`/`url()`
  instead of hardcoded URLs.
- Before hand-writing infrastructure (locks, queues, caches, HTTP clients,
  mailers, schedulers) or reaching for a third-party library, check whether a
  Symfony component covers it. It usually does.

Three specifics worth spelling out, because they are easy to get wrong:

- Bind request data with `#[MapRequestPayload]` / `#[MapQueryString]` on action
  arguments, which wires up Serializer and Validator for you, instead of calling
  `json_decode()` or `SerializerInterface` by hand. If neither package is
  installed yet, `composer require` them rather than falling back to manual
  parsing.
- Use constructor property promotion, and `readonly` for DTOs and value objects.
  Don't mark a service `readonly` if it might become `lazy: true`: a lazy proxy
  can't extend a `readonly` class.
- Use `symfony/lock` (`LockFactory`) for mutual exclusion. A hand-built flag or
  lock file looks fine in review and is usually wrong under concurrency.

## Design principles

Write code that follows SOLID. Concretely:

- **Single responsibility.** A class has one reason to change. Controllers
  only translate HTTP to a service call; business rules live in services.
- **Open/closed.** Add behaviour by adding a class, not by editing a switch.
  Use tagged iterators or `#[AsTaggedItem]` for strategies and handlers.
- **Liskov substitution.** Implementations honour their interface contract.
  No new exceptions or stricter preconditions in a subtype.
- **Interface segregation.** Keep interfaces small and role-specific. Don't
  force a class to implement methods it doesn't use.
- **Dependency inversion.** Depend on abstractions, injected through the
  constructor. Let autowiring resolve them; use `#[Target]` or `#[Autowire]`
  when the type alone is ambiguous.

Don't introduce an interface for a class with a single implementation unless
it's a boundary to an external system or a seam needed for tests. Prefer the
smallest design that satisfies these principles.

## Everyday workflow

- The development environment is executed under Docker containers.
  To run the app, containers defined in docker/compose must be running
  If containers are stopped, run them.
- Run commands inside the php container with commands with `symfony console ...`
- When something fails, read `var/log/dev.log` and the web profiler
  (`/_profiler`) before changing code.
- If `maker-bundle` is installed, prefer `bin/console make:*` with every argument
  passed up front and `--no-interaction` where supported: makers prompt on a
  terminal by default, which hangs a non-interactive shell. If a maker still
  needs interactive input, hand-write the code instead.
- If Doctrine ORM is installed, schema changes go through migrations
  (`bin/console make:migration`, then `doctrine:migrations:migrate`), never
  `doctrine:schema:update` or hand-written SQL.
- Don't execute migrations without ask permission
- `.env` is committed and holds defaults only. Real secrets belong in `.env.local`
  (git-ignored) or the secrets vault (`bin/console secrets:set`), read via
  `%env(...)%`.

## Testing

Install `symfony/test-pack` if it isn't already. Functional/HTTP tests extend
`WebTestCase`; service-level tests extend `KernelTestCase`. Run
`php bin/phpunit` (falls back to `vendor/bin/phpunit`). A feature isn't done
until it has a test that exercises it the way a caller would, an HTTP request for
a controller or a service call for a service, not just "it didn't throw."

## Code style

Symfony's coding standard, the `@Symfony` php-cs-fixer ruleset (a PSR-12-derived
superset). Run `vendor/bin/php-cs-fixer fix` if `friendsofphp/php-cs-fixer` is
installed; it isn't part of the skeleton by default.

## Static analysis

- PHPStan runs at level 9 over `bin/`, `config/`, `public/`, `src/` and `tests/`
  (config in `phpstan.dist.neon`). Run it inside the php container:
  `./dev.sh exec php composer phpstan`.
- A code change isn't done until PHPStan reports no errors. Fix the cause; don't
  add `@phpstan-ignore` or `ignoreErrors` entries without asking first.
- The Symfony extension reads `var/cache/dev/App_KernelDevDebugContainer.xml`. If
  PHPStan complains about unknown services, regenerate the dev cache
  (`./dev.sh exec php bin/console cache:warmup`) before changing code.

## Discover, don't guess

Framework APIs change between versions and your training data may be stale. Look
things up in the project instead of relying on memory:

- `bin/console about`: versions, environment, paths.
- `bin/console debug:router`, `debug:container`, `debug:autowiring <name>`,
  `debug:config <bundle>`, `config:dump-reference <bundle>`: what exists and how
  it is configured.
- `bin/console lint:container`, plus `lint:twig templates/` and
  `lint:yaml config/` where those packages are installed: validate before running.
- Docs: https://symfony.com/doc/current/ (switch to the version matching
  `composer.json` if it differs). Symfony itself has no `llms.txt`, but some
  dependencies do — prefer it as the entry point when one exists:
  `https://phpstan.org/llms.txt` (PHPStan) and
  `https://symfonycasts.com/llms.txt` (symfonycasts/verify-email-bundle).
- Read the installed source and docblocks under `vendor/` when the docs don't
  settle it — for the exact behaviour of the installed version, patched
  forks, or details the docs don't cover.
- For any third-party dependency listed in `composer.json` (Symfony components
  included), always consult its official documentation first when you need to
  understand how it works. Only fall back to reading the package's source
  under `vendor/` if the docs don't settle the question or don't cover the
  installed version.

## Architecture decisions

Read these before designing or generating code that touches their area. They
record decisions already made; don't re-open them without asking.

- `doc/architecture.md`: BFF role, SpaceTraders vs back-only endpoints, response
  contract and headers.
- `doc/authentication.md`: JWT auth (Lexik access token, custom refresh token
  rotation), token storage, firewall rules.
- `doc/openapi.md`: OpenAPI documentation setup and conventions.
- `doc/testing.md`: coverage setup, model/test factories, test doubles
  conventions.

When a decision changes, update the relevant file in the same change.

## Working agreements

How the user wants to work with Claude (confirmations, proposals, installs,
language). Read `doc/workflow.md` at the start of every session.
