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

## Subagents

- Never launch a subagent (the `Agent` tool, any `subagent_type`) without asking
  first and getting an explicit yes/no. This applies even when the task looks
  like a good fit for one; the user has no prior experience judging that
  tradeoff and wants to decide every time.
- The ask must let the user pick one of:
  - Approve launching the subagent as proposed.
  - Ask why a subagent is needed for this step, before deciding.
  - Do the work themselves (run the command, read the file, etc.) and paste the
    result back, instead of a subagent doing it.
- State plainly what the subagent would do and why delegating it helps (e.g.
  keeps noisy tool output out of the main context), so the user can make an
  informed choice or do it manually instead.
- When proposing a subagent, also propose a model for it, based on how much
  judgment the task needs:
  - Cheap model (haiku): the task has a closed, verifiable outcome and no
    design decisions — searching for a file/symbol, reading and summarizing a
    log or long file, running a command and reporting its output, mechanical
    multi-file edits (rename, reformat), checking whether something exists.
  - Default/stronger model: the task requires architecture or design choices,
    debugging with a non-obvious cause, reviewing code for subtle bugs, writing
    real business logic, or touches anything hard to undo (migrations,
    security, auth) where a near-correct answer could lead to a costly wrong
    decision.
  - Rule of thumb: if the expected result can be stated as one closed,
    checkable sentence, the cheap model is enough; if the subagent has to
    resolve ambiguity or make a design call, use the default model.

## Language

- Chat with the user in Spanish. Project documentation (`doc/*.md`, `AGENTS.md`)
  in English.

## Context

- Start a new conversation when a topic is closed. Point the new session to
  `AGENTS.md` and `doc/` instead of carrying the old history.
- Reference files by path instead of pasting their content into chat.
- Ask grouped questions when the answers are independent.
