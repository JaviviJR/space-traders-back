To use this kit, paste into a Claude Code session started at the target repo's root
(adjust the path if you copied the folder elsewhere):
`Read doc/spec-kit-implementation/GUIDE.md and follow it to set up this repository.`

# Spec-kit setup kit

You are the agent setting up this repository. Below, `<kit>` is the folder this guide
is in, relative to the repo root. Before each step that writes a file or runs a
command that changes state, say what it will change and wait for the user's
confirmation. Skip any step that is already done, and say so.

## 1. Workflow

Copy `<kit>/files/agent/workflow.md` to `doc/workflow.md`. If `doc/workflow.md` already
exists, merge: never overwrite or delete its existing content.

Then import it from `CLAUDE.md` with a bare `@doc/workflow.md` line. If there is no
`CLAUDE.md` but there is an `AGENTS.md`, create `CLAUDE.md` with `@AGENTS.md` and
`@doc/workflow.md`: Claude Code reads `AGENTS.md` only when there is no `CLAUDE.md`
(<https://code.claude.com/docs/en/memory>).

## 2. Spec-kit

1. **uv.** Check `uv --version`. If it's missing, don't install it yourself: give the
   user the installer command from
   <https://docs.astral.sh/uv/getting-started/installation/> and wait.
2. **Version.** If spec-kit is installed, use the version in
   `.specify/integration.json`. Otherwise ask the user which release to use, from
   <https://github.com/github/spec-kit/releases>. Run every `specify` command pinned:
   `uvx --from git+https://github.com/github/spec-kit.git@v<version> specify …`.
3. **Install.** `specify init --here --integration claude --force` (`--force` merges
   into a non-empty repo; it only writes `.specify/` and `.claude/skills/`). Check the
   flags with `specify init --help` for that version.
4. **Check.** `.specify/integration.json` names the `claude` integration, and
   `.claude/skills/` has the `speckit-*` skills.

## 3. Presets

Install every preset in `<kit>/files/presets/` (one folder per preset, each with a
`preset.yml`). If the folder is missing or empty, there's nothing to do.

For each one, tell the user what it changes (from its `preset.yml`) and whether its
`requires.speckit_version` matches the installed version, then run
`specify preset add --dev <kit>/files/presets/<id>`. Check with `specify preset list`.

Reference: <https://github.com/github/spec-kit/tree/main/docs/presets>.
