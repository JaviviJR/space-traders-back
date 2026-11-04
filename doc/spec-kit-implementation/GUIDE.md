To install this kit, paste into a Claude Code session started at the target repo's root
(adjust the path if you copied the folder elsewhere):
`Read doc/spec-kit-implementation/GUIDE.md and follow it to set up this repository.`

# Agent and spec-kit setup kit

You are the agent installing this kit. This guide tells you how. Below, `<kit>` is the
folder this guide is in, relative to the repo root.

## What the kit is

Two independent layers; install either or both:

| Layer | Source | Installed at | What it does |
|---|---|---|---|
| Agent | `<kit>/files/agent/workflow.md` | `doc/workflow.md`, imported from `CLAUDE.md` | How Claude works with the user in the repo. It never mentions spec-kit. |
| Spec-kit | `<kit>/files/spec-kit/preset/` | `presets/project-workflow/` | A spec-kit preset: clarify asks high-stakes questions with each option explained and a recommendation; plan presents each decision that way, one at a time, before writing `research.md`; `tasks.md` is for one developer working the stories in order. |

## How you work during the setup

- Before each step that writes a file or runs a command that changes state, say what it
  will change and wait for the user's confirmation.
- When an existing rule in the repo contradicts the kit, name both, quote them and ask
  which one wins before writing.
- When a decision has several viable approaches, explain each and recommend one.

## Step 0: what to install

Ask the user which layers to install. Then check and report, only for those layers:

- Agent layer: whether `CLAUDE.md` (at the root or in `.claude/`) and `AGENTS.md` exist,
  and whether `doc/workflow.md` exists.
- Spec-kit layer: whether `uv` is available (`uv --version`); whether spec-kit is
  installed (`.specify/integration.json`) and its version; files in
  `.specify/templates/overrides/`.

## Part 1: agent layer

### 1. Copy

Copy `<kit>/files/agent/workflow.md` to `doc/workflow.md`. If `doc/workflow.md` already
exists, merge: never overwrite or delete its existing content.

### 2. Import

Add the import in the file Claude Code actually reads
(<https://code.claude.com/docs/en/memory>: it reads `AGENTS.md` only when there is no
`CLAUDE.md`):

| The repo has | Do |
|---|---|
| Neither | Create `CLAUDE.md` with `@doc/workflow.md`. |
| Only `CLAUDE.md` | Add `@doc/workflow.md` to it; its existing content stays. |
| Only `AGENTS.md` | Create `CLAUDE.md` with `@AGENTS.md` and `@doc/workflow.md`. `AGENTS.md` stays untouched, so other tools keep reading it as before. |
| Both | Add `@doc/workflow.md` to `CLAUDE.md`. |

The import path is relative to the file that contains it: adjust it if `CLAUDE.md` is
in `.claude/`.

## Part 2: spec-kit layer

### 1. uv

Skip this step if `uv --version` works. Otherwise never install it yourself: give the
user the standalone installer command from
<https://docs.astral.sh/uv/getting-started/installation/>, wait for them to run it, then
check `uv --version`. If the shell doesn't find it, the installer printed the directory
to add to `PATH`.

### 2. Version

If spec-kit is installed, use the version in `.specify/integration.json`. If it is
below `1.2.5` (the preset's `requires`), stop: the preset can't be installed until the
user upgrades spec-kit.

If it isn't installed, ask the user which release to use, at least `1.2.5`, from
<https://github.com/github/spec-kit/releases>.

Every command below is
`uvx --from git+https://github.com/github/spec-kit.git@v<version> specify …`, run from
the repo root. Never run it without the pin: `uvx` would take the latest release.

### 3. Install spec-kit

Skip this step if spec-kit is installed. Otherwise run
`specify init --here --integration claude`. In a non-empty directory it asks for
confirmation and fails without a terminal: add `--force`, which merges into the existing
files (it writes only `.claude/skills/` and `.specify/`). Check flag names with
`specify init --help` for the pinned version.

### 4. Preset

If `.specify/templates/overrides/` has a file for a template the preset extends
(`tasks-template.md`), tell the user: an override takes precedence over every preset, so
the preset's addition won't apply while it exists. Ask whether to keep or delete it.

Copy `<kit>/files/spec-kit/preset/` to `presets/project-workflow/` (outside `.specify/`,
so it's tracked and editable), then run:

```sh
specify preset add --dev presets/project-workflow
```

### 5. Check

- `grep -n "Project rules for this phase" .claude/skills/speckit-{clarify,plan}/SKILL.md`
  finds both, at the end of each file.
- `specify preset resolve tasks-template` shows `project-workflow` composed on top of
  the core template.
