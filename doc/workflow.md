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
- Options: when a decision has several viable approaches, explain each one
  before settling on a choice, so the user understands the trade-offs and can
  disagree with informed context; bare option labels aren't enough. For each,
  cite what the official docs say about it (check them first, per AGENTS.md →
  "Discover, don't guess"), or say explicitly that they have no stance and
  it's a project decision.
- In each option or rule, compare the resulting code: which is simpler and
  which adds complexity that may be unnecessary (extra state, coordination,
  files touched). Rules are defaults, not dogma: state the condition that
  overrides one on simplicity grounds.
- When a decision comes from an installed skill, say which skill and why it
  recommends it.
- Testing: every proposal — the initial one or a later split into sequential
  blocks — must say what will be tested and how: which PHPUnit test(s) (new
  or updated, with file path) cover the change, per [testing.md](testing.md),
  and whether a functional HTTP test (`WebTestCase`) applies or doesn't and
  why. State this alongside the files-affected list, not as an afterthought
  once the change is already applied.
- When a decision is closed, propose where to record it, as
  [AGENTS.md → Where documentation lives](../AGENTS.md#where-documentation-lives)
  says, or this file for working agreements. Ask before writing (see
  "Confirmations"), then reply with a one-line summary.
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

## Checks and commits

- When `vendor/bin/php-cs-fixer fix` reformats files outside the change's
  scope, keep those edits: don't revert them to keep the diff scoped and
  don't ask whether to keep them. List the files it touched so the user knows
  what to expect in `git diff`.
- The user makes the commits. Don't ask whether to commit: once a piece of
  work is done and verified, say so and stop. When the work is split into
  blocks, say when a block ends so the user can commit there. The only
  exception is a `/feature-session` (see "Spec-kit features").

## Spec-kit features

- Features are built with spec-kit. Each one goes through `/speckit-specify`,
  `/speckit-clarify`, `/speckit-plan`, `/speckit-tasks` and `/speckit-analyze`,
  each reviewed by the user before the next. Its artifacts live in
  `specs/<NNN-feature>/`.
- `/speckit-tasks` groups tasks by user story (each story touches every layer
  it needs), not by layer, and ends with a "Sessions" table (from
  `.specify/templates/overrides/tasks-template.md`) whose "Command" column
  holds the exact `/feature-session` line to paste for each row.
- Implementation: one new Claude Code conversation per row of that table,
  started with `/feature-session <feature dir> <session id>`. The context
  comes from the feature's artifacts, not from chat history. Progress is the
  `[X]` marks in `tasks.md`.
- Closing a session: apply the changes, run the session's checks and mark its
  tasks `[X]`, then stop and wait for the user's review. If they ask for
  changes, apply them and wait again; once they say it's all OK, Claude makes
  the session's commit.
- Session commit message: the session in the subject, to read at a glance in
  `git log --oneline`, and as trailers, to query with
  `git log --format='%(trailers:key=Session)'`:

  ```
  <type>(<NNN-feature>): <session id> <summary>

  <optional body: what else the session touched>

  Session: <NNN-feature> <session id>
  Tasks: <task IDs>
  ```

  `<type>` is the Conventional Commits type of the main change. The feature
  goes in the scope because the spec defines it as a section of the codebase;
  a session isn't one, so it goes in the description.
- `/speckit-converge` (optional, once every session is done): the
  `/feature-session` sessions count as the `/speckit-implement` run it
  requires. If it appends a `## Phase N: Convergence`, Claude ends that same
  section with its own `### Sessions` table, numbered after the last session
  and following the template's columns (including "Command") and its grouping
  and "Close" rules, instead of adding rows to the existing table (converge
  only appends). The handoff is `/feature-session`, never
  `/speckit-implement`.
- Never run `/speckit-implement`: it runs every phase in one go and creates or
  rewrites ignore files (here, appends to `.gitignore` and creates a
  `.dockerignore` because of `docker/*/Dockerfile`). Its skill, and the
  deprecated `speckit-taskstoissues`, carry `disable-model-invocation: true`
  so only the user can launch them and their descriptions stay out of every
  session's context. A spec-kit upgrade regenerates `.claude/skills/speckit-*`:
  after one, check that both still have it. Rejected: the Claude
  integration's `--no-model-invocation` option (survives upgrades, but makes
  every `speckit-*` skill user-only); revisit if the manual check gets missed
  after an upgrade.

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
- Constitution (`.specify/memory/constitution.md`): English, like the
  `AGENTS.md` and `doc/` it indexes.
- Feature artifacts (`specs/**`): content in Spanish, so the user reviews them
  in their own language, but the section headings of the `.specify/templates/`
  and the structural keywords the `speckit-*` skills look for (e.g.
  "Constitution Check", task IDs) stay in English, so the templates and skills
  work unchanged across spec-kit upgrades.
  - Revisit: English would save ~2k tokens per `/feature-session`. When the
    user asks to switch, drop this rule so the `speckit-*` skills generate in
    English as they do by default, and ask whether existing `specs/**` get
    translated or stay as they are.

## Context

- Start a new conversation when a topic is closed. Point the new session to
  `AGENTS.md` and `doc/` instead of carrying the old history.
- Reference files by path instead of pasting their content into chat.
- Ask grouped questions when the answers are independent.
