---
name: "feature-session"
description: "Implement one session (a row of the Sessions table in tasks.md) of a spec-kit feature, following the project's proposal workflow."
argument-hint: "<feature dir, e.g. specs/001-feature-name> <session id, e.g. S1>"
user-invocable: true
disable-model-invocation: true
---

## User Input

```text
$ARGUMENTS
```

The first argument is the feature directory, the second the session id. If either is
missing, or the directory has no `tasks.md` with a "Sessions" table listing that
session, stop and ask.

## Steps

1. Follow `AGENTS.md` and `doc/workflow.md` to the letter (proposal: no diffs or pasted
   code in chat, list every file, functional effect + design rationale, say what is
   tested; ask for explicit confirmation before writing any file; after applying, wait
   for the user's review). `doc/workflow.md` → "Spec-kit features" explains this flow.
2. Read `<feature dir>/tasks.md`. The "Sessions" tables (the main one and one per
   `Convergence` phase, if any) say which tasks belong to this session and how it closes. Check that every task of earlier sessions is marked `[X]`;
   if not, stop and tell the user which ones are missing.
3. Read `<feature dir>/spec.md` and `plan.md`, and from `research.md` only the decisions
   cited by this session's tasks. Read the `doc/*.md` files whose `AGENTS.md` trigger
   matches the work.
4. Implement only this session's tasks, one proposal per task or per closely related group
   (list each task ID covered). Do NOT run `/speckit-implement`.
5. Close as the "Sessions" table says: run each check, report each result, keep
   php-cs-fixer's edits to unrelated files and list them, and mark the finished tasks `[X]`
   in `tasks.md`.
6. Stop and wait for the user's review. Apply any changes they ask for and wait again.
   Once they say it's all OK, commit the session with the message format in
   `doc/workflow.md` → "Spec-kit features".
