# Alternative: PCOV instead of Xdebug for coverage

Alternative to the decision recorded in "Code coverage" in
[../testing.md](../testing.md) (coverage driven by Xdebug, enabled per-run
with `XDEBUG_MODE=coverage`).

If the suite grows and per-run coverage becomes slow, switch to
[PCOV](https://github.com/krakjam/pcov) instead of Xdebug for coverage.
PCOV only instruments coverage (no step debugging, no profiling), which
makes it faster than Xdebug for this specific job, at the cost of one more
extension to install and maintain alongside Xdebug in
`docker/php/Dockerfile`. Not needed today; revisit if coverage runs become
a bottleneck.
