# Working agreements

How the user works with Claude on this project. Applies to every session.

## Preferences

- Language: chat with the user in Spanish. Project documentation in English.
- User-run commands: never run package installs (npm, editor extensions),
  even when the permission mode would allow it. Give the exact commands as
  one copy-paste block, with a short note on what each does and a link to the
  official docs, wait for the user to run them, then verify the result by
  reading files.
- Commits carry no `Co-Authored-By` trailer or any other AI attribution line,
  whatever Claude Code's default says.
- Subagent model: pass a cheap model when the expected result can be stated
  as one closed, checkable sentence; use the default model when the subagent
  has to resolve ambiguity or make a design call.

## Options

Why: the user picks knowing what each choice costs.

- When a decision has several viable approaches, explain each one before the
  user picks: what it does, and what the official docs recommend about it
  (check them first) — or say explicitly that they have no stance and it's a
  project decision.
- In each option, compare the resulting code: which is simpler and which adds
  complexity that may be unnecessary (extra state, coordination, files
  touched). Say under which condition another option would win.
- When an option clashes with a project rule: rules are defaults, not dogma;
  say under which condition breaking it would pay off on simplicity grounds.
- End with a recommendation.
- Decisions go one at a time: present one, wait for the user's answer, then
  the next.

## Learning mode

Why: the user is using this project to learn Vue.js.

- Explain every change didactically, as if it were a guided exercise: what
  it does and which Vue concepts it involves.
  When code makes the explanation clearer, show it — a snippet from a file, a
  diff, or a minimal example of a concept — alongside the explanation, never
  instead of it. List every file affected; the user reviews the actual change
  in the permission prompt or with `git diff`.
- Design rationale: for a structural change, split the explanation in two:
  what the change does (functional effect) and why this design specifically —
  what coupling, responsibility or extensibility problem it solves, or which
  principle it applies, and why a simpler alternative wasn't enough. A
  structural explanation without the second part is incomplete.
  - Structural: a new or changed interface (a component's inputs/outputs, a
    module's public API, an exported function's signature), a design pattern
    introduced, logic moved between layers, or a file or module created or
    extracted.
  - Anything else (a local rename, extracting a function within the same
    file, a typo, a stale reference): the functional effect in one line,
    naming the rule applied.
- When a decision comes from an installed skill, say which skill and why it
  recommends it.
- Invite questions, answer them, and apply the change only once the user says
  they have no more doubts.
- Known concepts: the ones listed below are concepts the user already knows:
  name them without explaining them. Exception: when one shows up in a way
  the user hasn't seen yet (a new use, option or interaction with another
  API), explain only that new part. When the user says they already know a
  concept, propose adding it here; if they say they've forgotten one, propose
  removing it.
  - `ref`
  - `computed`
  - `v-if` / `v-else` / `v-else-if`
  - A composable returning functions the component uses as event handlers
  - `readonly` on a composable's returned state
