# Testing

## Code coverage

Coverage is driven by Xdebug, already installed in the `dev` image
(`docker/php/Dockerfile`). Xdebug's default mode there is `debug` (step
debugging for the IDE), so coverage is enabled per-run instead of changing
that default: `composer test-coverage` sets `XDEBUG_MODE=coverage` only for
that command, leaving IDE debugging untouched for every other run.

Reports are configured in `phpunit.dist.xml` (`<coverage>`): an HTML report
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
from tests.

## Test factories

`UserFactory` (`src/Factory/UserFactory.php`) is the first Foundry factory;
it defaults `isVerified` to `true`, so factory-made users don't need manual
verification in tests. New entities that need test fixtures should get
their own Foundry factory too, for consistency.

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
  - **Extract to a named class** (e.g. `FakeSpaceTradersGateway`,
    `FakeSpaceTradersRouteHook`) once the same shape is duplicated across
    tests, and make it configurable with `willXxx()` setters instead of
    hard-coding behaviour in the constructor.
- **Plain data objects** (a type whose only job is to hold public properties
  read by something like Symfony's `ObjectNormalizer`, not to be called):
  don't double these with `createMock()` — PHPUnit's doubles only configure
  methods (and, since PHP 8.4, declared property hooks), not arbitrary
  public properties. Just instantiate a real, typed fixture object.

## TODO

- Revisit the test doubles guidance above once more of the suite is written;
  it's based on one service's tests (`SpaceTradersProxyServiceTest`,
  `EnvelopeBodyTest`) and may need adjusting as other patterns show up.
- If the suite grows and per-run coverage becomes slow, switch to
  [PCOV](https://github.com/krakjam/pcov) instead of Xdebug for coverage.
  PCOV only instruments coverage (no step debugging, no profiling), which
  makes it faster than Xdebug for this specific job, at the cost of one more
  extension to install and maintain alongside Xdebug in
  `docker/php/Dockerfile`. Not needed today; revisit if coverage runs become
  a bottleneck.
