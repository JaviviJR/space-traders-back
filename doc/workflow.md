# Working agreements

How the user works with Claude on this project. Applies to every session.

## Confirmations

- Ask explicit confirmation before writing files tracked by git, or running
  commands that change state. Files outside git can be written without asking.
- Option answers are not approval. Approval for one change does not extend to
  the next.
- Related changes may be grouped into one proposal, provided each change is
  listed explicitly so the approval covers each one.

## Proposals

- New file: don't paste its content in chat, before or after writing it.
  Explain what it does and why, then write it. The user reviews the content in
  the editor or with `git diff`.
- Existing file, small change: show a diff (`-`/`+` lines with a bit of
  context), not the full document.
- Existing file, large rewrite: a diff would cost more tokens than it saves.
  Explain what's changing instead of pasting a diff or the full content. The
  user reviews the result in the editor or with `git diff`.
- For code changes, the same rules apply: describe responsibility, public
  signatures and what changed; never paste the full code in chat.
- When a decision is closed, write it to the relevant `doc/*.md` file and reply
  with a one-line summary.

## Steps and installs

- Design decisions: one at a time, each with its trade-offs and a recommendation.
- Installation and setup steps: give the commands as one copy-paste block, with
  a short note on what each does and a link to the official docs. The user runs
  them.
- Never run package installs (composer, npm, editor extensions). Give the exact
  command and wait for the result, then verify it by reading files.

## Language

- Chat with the user in Spanish. Project documentation (`doc/*.md`, `AGENTS.md`)
  in English.

## Context

- Start a new conversation when a topic is closed. Point the new session to
  `AGENTS.md` and `doc/` instead of carrying the old history.
- Reference files by path instead of pasting their content into chat.
- Ask grouped questions when the answers are independent.
