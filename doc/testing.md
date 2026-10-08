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

## TODO

- Integrate [Foundry](https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html)
  (`zenstruck/foundry`) into the project for test model factories, instead of
  building entities by hand or reaching into the `EntityManager` from tests.
- If the suite grows and per-run coverage becomes slow, switch to
  [PCOV](https://github.com/krakjam/pcov) instead of Xdebug for coverage.
  PCOV only instruments coverage (no step debugging, no profiling), which
  makes it faster than Xdebug for this specific job, at the cost of one more
  extension to install and maintain alongside Xdebug in
  `docker/php/Dockerfile`. Not needed today; revisit if coverage runs become
  a bottleneck.
