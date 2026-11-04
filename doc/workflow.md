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
