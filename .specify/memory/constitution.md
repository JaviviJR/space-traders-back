# Space Trader Back Constitution

## Core Principles

Each principle is a gate that /speckit-plan's 'Constitution Check' MUST verify. The rule's
text and its rationale live in the linked document; if they change there, this constitution
stays untouched.

### I. Idiomatic Symfony, thin controllers

The plan MUST show that new capabilities come from Symfony components installed with Flex,
framework metadata is declared with PHP attributes, services are autowired, and controllers
only translate HTTP to a service call.
Source: AGENTS.md (Adding features, Conventions).

### II. The BFF contract is kept

The plan MUST classify each endpoint as SpaceTraders-proxied or back-only and show that it
follows the response envelope, error mapping and OpenAPI rules.
Source: doc/architecture.md, doc/spacetraders-proxy.md, doc/errors.md, doc/openapi.md, and
doc/authentication.md for anything touching auth.

### III. The smallest structure that satisfies SOLID

The plan MUST justify each new class, interface or layer against a smaller option.
Source: AGENTS.md (Design principles) and doc/workflow.md (Proposals).

### IV. Tested the way a caller would use it (NON-NEGOTIABLE)

The plan MUST state which PHPUnit tests (WebTestCase for HTTP, KernelTestCase for services)
cover each change.
Source: AGENTS.md (Testing), doc/testing.md and doc/workflow.md (Proposals).

### V. Quality checked by tools, without silencing errors

The plan MUST end with php-cs-fixer and PHPStan level 9 clean, with no new ignores.
Source: AGENTS.md (Code style, Static analysis).

## Additional Constraints

Stack, installs, migrations and secrets: AGENTS.md (Stack, Adding features, Everyday workflow).

## Development Workflow

Proposals, confirmations, installs, commits and the spec-kit flow: doc/workflow.md.
Recording decisions: AGENTS.md (Where documentation lives).

## Governance

- AGENTS.md and doc/*.md are the source of truth. This constitution only lists the plan's
  gates and MUST NOT repeat their text, so it cannot drift out of sync.
- If this constitution disagrees with AGENTS.md, doc/*.md or a skill, the user MUST be
  warned before acting, as AGENTS.md (Conflicts between sources) says.
- Amendments MUST be proposed to the user and applied only with their approval. The
  constitution changes only when a gate is added, removed or redefined, not when a linked
  document's detail changes.
- Semantic versioning: MAJOR when a gate is removed or redefined; MINOR when one is added;
  PATCH for wording.
- Compliance: a violation in the plan is accepted only when justified in its
  "Complexity Tracking" section.

**Version**: 1.0.0 | **Ratified**: 2026-10-31 | **Last Amended**: 2026-10-31
