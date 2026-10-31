---
name: "context-audit"
description: "Audit how much context the project's markdown (CLAUDE.md, AGENTS.md, doc/, memory, skills, spec-kit) consumes, find duplicated, stale or contradictory instructions, and propose prioritized fixes."
argument-hint: "[optional focus, e.g. memory | docs | skills | language]"
user-invocable: true
disable-model-invocation: true
allowed-tools: Bash(${CLAUDE_SKILL_DIR}/scripts/measure.py *)
---

<!--
Runs in the main conversation, not with `context: fork`: the audit ends in a
proposal the user approves and Claude applies, and a forked subagent would
only return a summary, so every file would be read twice. The script keeps the
reading cost low, and the audit gets its own conversation per doc/workflow.md
→ "Context".
-->

## User Input

```text
$ARGUMENTS
```

If a focus is given, run every step but report and propose only on that area.

## Rules

- Follow `AGENTS.md` and `doc/workflow.md` to the letter: no diffs or pasted
  content in chat, every file listed, functional effect + design rationale,
  what gets tested. Change nothing until the user approves.
- Never open `doc/prompts/`: the script reports its size only.
- Don't edit the bodies of vendor skills (`speckit-*`, `php-pro`): an upgrade
  overwrites them. Frontmatter overrides are fine only when `doc/workflow.md`
  records them.

## Closed decisions

Don't propose these again unless their condition changed; mention them in one
line if the numbers moved.

- `specs/**` stays in Spanish: see the "Revisit" note in `doc/workflow.md` →
  "Language".
- `doc/workflow.md` → "Spec-kit features" stays always-loaded: moving it to an
  on-demand doc saves ~450 tokens per session, but if its trigger is missed,
  the spec-kit skills run without the language rule and nothing warns.

## Steps

1. Measure: run
   `${CLAUDE_SKILL_DIR}/scripts/measure.py --root . --size-only doc/prompts`.
   It inventories the markdown, classifies the load where a fixed rule decides
   it, estimates tokens and the saving if Spanish prose were English, and lists
   unresolved `[[links]]` in memory. Its header explains the ratios; quote them
   in the report and say the numbers are estimates. For real numbers, suggest
   the user runs `/context` (Claude can't run it).
2. Check what needs judgment, reading only the files involved:
   - Duplicates: a memory that repeats `doc/workflow.md`, `AGENTS.md` or
     another `doc/*.md`. A permanent working agreement belongs in `doc/`
     (AGENTS.md → "Where documentation lives"), not in local memory.
   - Stale memory: for each `project` memory, compare its state claims
     ("uncommitted", "in progress", "next: ...") with `git log` and
     `git status`, and its decisions with what the code and `doc/` already
     record.
   - Contradictions: a rule in `doc/workflow.md` or `AGENTS.md` that the
     configuration doesn't enforce or contradicts (skill frontmatter, the
     constitution, memory).
   - Overrides lost to a spec-kit upgrade: every project override that
     `doc/workflow.md` → "Spec-kit features" lists is still in place.
   - Dated "Revisit" notes in `doc/` and memory: list each with its date and
     ask whether it's time to decide.
   - Always-loaded text only useful for one kind of task: candidate for an
     on-demand doc, weighing the cost of a missed trigger.
   - The script's unresolved memory links.
3. Report, in Spanish: the script's table, the ranking by real impact (tokens
   × load frequency), which files are worth translating and which aren't, and
   each finding from step 2.
4. Propose a prioritized plan as numbered items the user can approve one by
   one. Testing: these changes touch no `src/` or `tests/`, so say no PHPUnit
   test applies; php-cs-fixer and PHPStan don't read markdown.
5. Wait. Apply only the approved items, record each closed decision where
   AGENTS.md → "Where documentation lives" says (and add a rejected one to
   "Closed decisions" above), then report what changed per file.
