# Working agreements

How the user works with Claude on this project. Applies to every session.

## Confirmations

- Ask explicit confirmation before writing any file, or running commands that
  change state. This applies regardless of whether the file is tracked by
  git.
- Option answers are not approval. Approval for one change does not extend to
  the next.
- Related changes may be grouped into one proposal, provided each change is
  listed explicitly so the approval covers each one.

## Proposals

- File changes: never show a diff or pasted content in chat, regardless of
  whether it's a new file, a small edit or a large rewrite. Explain what
  changed and why, listing every file affected, so the user knows what to
  review. The user reviews the actual change with `git diff` in VSCode.
- Design rationale: when a code change is driven by a design pattern, a SOLID
  principle, an architecture decision or a code-quality improvement — however
  small, including a rename or extracting a method — split the explanation in
  two: what the change does (functional effect) and why this design
  specifically — what coupling, responsibility or extensibility problem it
  solves, or which principle it applies, and why a simpler alternative wasn't
  enough. Always explicit, even for small changes. A proposal without the
  second part is incomplete.
  - Not for corrections that involve no design choice (a stale reference, a
    typo, a fact that doesn't match the code, moving text by applying a rule
    already written down): one line naming the rule applied is enough.
- When a decision is closed, propose where to record it: the place AGENTS.md's
  "Architecture decisions" rule says, or this file for working agreements.
  Ask before writing (see "Confirmations"), then reply with a one-line
  summary.
- Ask for confirmation on doc/*.md updates separately from the confirmation on
  the implementation change itself, even when they're part of the same
  proposal. Implementing something and documenting it are different
  decisions; bundling them into one yes/no hides the second one.

## Steps and installs

- Design decisions: one at a time, each with its trade-offs and a recommendation.
- Installation and setup steps: give the commands as one copy-paste block, with
  a short note on what each does and a link to the official docs. The user runs
  them.
- Never run package installs (composer, npm, editor extensions). Give the exact
  command and wait for the result, then verify it by reading files.
- When asked to split a plan into sequential blocks, reorder the explanations
  already given as-is into blocks — don't compress or re-summarize them into
  shorter bullets. The functional-effect / design-rationale split from
  "Proposals" above still applies per item once grouped into blocks.
- Order blocks by real dependency (e.g. install before code, model before
  service, service before controller), not by an arbitrary partition of the
  content. If the plan has no valid linear order (independent blocks, a
  circular dependency, or a block needing decisions from several later blocks
  at once), say so explicitly and propose how to resolve it (parallel blocks,
  merging blocks, asking for a decision first) instead of forcing a
  sequence.

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
