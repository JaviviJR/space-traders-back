# Testing

## Code coverage

Coverage is driven by Xdebug, already installed in the `dev` image
(`docker/php/Dockerfile`). Xdebug's default mode there is `debug` (step
debugging for the IDE), so coverage is enabled per-run instead of changing
that default: `composer test-coverage` sets `XDEBUG_MODE=coverage` only for
that command, leaving IDE debugging untouched for every other run.

Reports are configured in `phpunit.coverage.xml` (`<coverage>`): an HTML report
in `var/coverage/html` (already covered by the `/var/` entry in `.gitignore`)
and a text summary printed to the console.

Run it inside the php container:

```
composer test-coverage
```

## Model factories

[Foundry](https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html)
(`zenstruck/foundry`) is installed (`require-dev`) for test model factories,
instead of building entities by hand or reaching into the `EntityManager`
from tests. Every entity that needs test fixtures gets its own factory in
`src/Factory/`.

## Test doubles

Criteria for choosing between PHPUnit's generated doubles (`createStub()` /
`createMock()`) and a hand-written double (anonymous class or a named Fake):

- **`createStub()`**: a collaborator that just needs to return canned values
  (no interaction to verify). Use for simple interfaces consumed for their
  return value only.
- **`createMock()`**: when the test needs to verify *how* a collaborator was
  called (`expects()`, `with()`). To assert on an argument's contents without
  a hand-rolled spy, use an argument matcher instead of capturing state
  manually: `->with(self::callback(function (array $options): bool { ...;
  return true; }))`.
- **Hand-written double (anonymous class, or a named Fake under
  `tests/.../Fake*`)**: when the collaborator needs real, coherent behaviour
  across several methods (e.g. a strategy/hook interface where `supports()`
  and `beforeForward()`/`afterForward()` must agree), or when it needs to
  carry state between calls. PHPUnit's generated doubles configure each
  method independently and can't express that coherence without
  `willReturnCallback()` closures, which end up being the method body
  anyway, just harder to read and untyped. A real class implementing the
  interface also fails to compile when the interface gains a method, whereas
  a generated mock silently returns `null` for it — catching contract drift
  that a mock would hide.
  - Keep it as an **inline anonymous class** when it's used once and the
    shape is trivial.
  - **Extract to a named class** (e.g. `App\Tests\SpaceTraders\Proxy\FakeGateway`,
    `App\Tests\SpaceTraders\Proxy\FakeRouteHook`) once the same shape is duplicated across
    tests, and make it configurable with `willXxx()` setters instead of
    hard-coding behaviour in the constructor.
- **Plain data objects** (a type whose only job is to hold public properties
  read by something like Symfony's `ObjectNormalizer`, not to be called):
  don't double these with `createMock()` — PHPUnit's doubles only configure
  methods (and, since PHP 8.4, declared property hooks), not arbitrary
  public properties. Just instantiate a real, typed fixture object.

## Asserting JSON responses

`mtdowling/jmespath.php` is installed (`require-dev`) so zenstruck/browser's
[JSON assertions](https://github.com/zenstruck/browser#json) take a JMESPath
selector. Pick the assertion by what the test checks:

- **Individual fields**: `assertJsonMatches('error.code', ...)` on the
  browser, and `json()->assertMissing('data.password')` for a key that must
  not be there. The selector lives in the assertion, so a failure names it,
  instead of an `@var array{...}` shape that PHPStan trusts but nothing
  checks, and that fails with "Undefined array key" when the body drifts.
- **The exact body is the contract** (e.g. an error response that must carry
  nothing else, or two responses that must be indistinguishable): compare the
  whole `json()->decoded()` with `assertSame`. It catches extra keys and type
  changes that field-by-field matching doesn't.
- **Extracting a value** to use later (an access token, an id to compare with
  the database): `json()->search()` (or `decoded()`), narrowed with an
  `assertIs*()` since it returns `mixed`.

`assertJsonMatches` compares with `==`, not `===` (it goes through
zenstruck/assert's `equals()`): `assertJsonMatches('id', 1)` passes for
`"1"`. When the type is part of what's tested, use `search()` plus
`assertIsInt()`/`assertSame()` instead.

## TODO

- Revisit the test doubles guidance above once more of the suite is written;
  it's based on one service's tests (`App\Tests\SpaceTraders\Proxy\ProxyServiceTest`,
  `App\Tests\SpaceTraders\Response\HttpEnvelopeMergerTest`) and may need adjusting as other patterns
  show up.
- Switch to PCOV for coverage if coverage runs become slow.
