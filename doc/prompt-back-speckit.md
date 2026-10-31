# Integrar spec-kit en el back y alinear su workflow con el del front

## Contexto

El front (SPA Vue 3 + Vite, mismo usuario, mismo producto) ha adoptado
spec-kit **v1.2.5** como flujo para desarrollar features. Se probó con una
feature completa (`specs/001-confirm-email`, 6 sesiones) y el usuario lo eligió
frente a su flujo anterior por bloques horizontales, porque prefiere tareas
agrupadas en vertical por historia de usuario.

No tienes acceso al repo del front. Todo lo que necesitas está en los anexos
de este documento, copiado literalmente de los ficheros del front.

## Reglas para esta tarea

- Sigue tu propio `AGENTS.md`, tu workflow y tu memoria (propuestas antes de
  cambiar nada, modo aprendizaje si lo tienes, nunca ejecutar instalaciones de
  paquetes, etc.). Si algo de este documento contradice tus reglas, avísale al
  usuario antes de actuar: cita las dos fuentes y di cuál sigues y por qué.
- No cambies ningún fichero hasta que el usuario lo apruebe. Trabaja por fases
  y para al final de cada una.
- Lo que sea específico del front (Vue, Pinia, PrimeVue, Vitest, Playwright,
  Prettier, ESLint, oxlint, npm, `src/`) no se copia tal cual: tradúcelo a su
  equivalente del back o descártalo diciendo por qué.
- Habla con el usuario en español.

## Fase 1: comparar memoria y workflow (solo lectura)

Compara tu memoria (`MEMORY.md` y sus ficheros) y tus reglas de trabajo (tu
equivalente a `doc/workflow.md` y las secciones de `AGENTS.md` de las que
depende) con los anexos A, B y C. Para cada regla o memoria del front,
clasifícala en uno de estos grupos:

1. **Igual en el back** → se ignora. Solo nómbrala en una línea, para que el
   usuario vea que la has revisado.
2. **Dice lo mismo, pero el front lo define mejor** → propón actualizar el
   texto del back. Di en qué fichero y qué aporta la versión del front (un
   porqué, un caso concreto, una condición para revisarla, un límite más
   claro). Si es al revés y la del back está mejor definida, dilo y no la
   toques.
3. **Nueva para el back** → evalúa si aplica y recomienda: incluir, adaptar
   (di cómo) o descartar (di por qué). Si es específica del front, basta una
   línea.

Además:

- Decide dónde va cada cosa que se incluya según tu `AGENTS.md` → "Where
  documentation lives" (o su equivalente; en el front está en el anexo B): un
  acuerdo de trabajo permanente va en el workflow versionado, no en la memoria
  local. Si una memoria del back repite algo que tras este cambio quedará en el
  workflow, propón borrarla.
- Si encuentras reglas del back que contradicen las del front, nómbralas
  aparte, sin cambiarlas: el usuario decidirá si las lleva al front.
- La sección "Known concepts" del front es una lista de conceptos de Vue que
  el usuario ya conoce. Si el back adopta el mecanismo, la lista es propia del
  back (puede empezar vacía); no copies la del front.
- La nota "Revisit" de "Design rationale" tiene fecha de inicio 2026-10-08 en
  el front. Si el back adopta la regla, decide con el usuario si hereda esa
  fecha o empieza a contar ahora.

Entrega el resultado como lista numerada, agrupada por los tres grupos de
arriba, para que el usuario apruebe punto por punto. Para y espera.

## Fase 2: instalar spec-kit

1. Comprueba que el árbol de git está limpio y que estás en una rama nueva, de
   modo que `git status` y `git diff` muestren después exactamente lo que ha
   hecho el instalador.
2. Dale al usuario el comando de instalación en un bloque para copiar y pegar,
   con una nota de qué hace y el enlace a la documentación oficial
   (https://github.com/github/spec-kit). No lo ejecutes tú. Usa la misma
   versión que el front (v1.2.5), para que los dos proyectos compartan
   plantillas y skills. En el front se instaló con
   `uvx --from git+https://github.com/github/spec-kit.git@v1.2.5 specify init --here --integration claude --script sh`.
   Comprueba esa sintaxis en la documentación de esa versión antes de dársela
   al usuario.
3. Cuando el usuario lo haya ejecutado, revisa con `git status` qué ha creado
   o modificado el instalador (presta atención a los ficheros de ignore) y
   cuéntaselo al usuario.

## Fase 3: adaptar spec-kit al back (proponer, esperar aprobación, aplicar)

Propón estas piezas, cada una explicada según tu workflow:

a. **Override de la plantilla de tareas** (anexo E) en
   `.specify/templates/overrides/tasks-template.md`. spec-kit busca primero en
   `overrides/` y las actualizaciones solo sustituyen la plantilla base, así
   que el override sobrevive a ellas. Cambia respecto a la base: los tests
   son obligatorios pero sin imponer TDD, desaparece "commit after each task"
   y la estrategia de equipo paralelo se sustituye por una tabla "Sessions"
   (un desarrollador, una conversación de Claude Code por fila). Adapta al
   back la columna "Close" del ejemplo y los números de principio de la
   constitución que se citan.
b. **`disable-model-invocation: true`** en el frontmatter de
   `.claude/skills/speckit-implement/SKILL.md` y
   `.claude/skills/speckit-taskstoissues/SKILL.md`, con el comentario
   `# Project override: re-apply after a spec-kit upgrade (...)` que se ve en
   el anexo D3. Así solo el usuario puede lanzarlas y sus descripciones no
   ocupan contexto en cada sesión.
c. **Skill `feature-session`** (anexo D1), en
   `.claude/skills/feature-session/SKILL.md`. Es genérica: solo cambia nombres
   si tus documentos se llaman distinto.
d. **Skill `context-audit`** (anexo D2), en `.claude/skills/context-audit/`,
   con su script `scripts/measure.py` (déjalo ejecutable). Adapta la lista de
   skills de terceros que no se deben editar a las que tenga el back. Las
   "Closed decisions" son del front: conserva cada una solo si el back adopta
   la misma regla en la Fase 1; si no, elimínala.
e. **Constitución**: la crea el usuario con `/speckit-constitution`. El
   diseño que se cerró en el front (anexo F) es un índice de puertas
   ("gates") que comprueba el "Constitution Check" de `/speckit-plan`. Cada
   puerta apunta al documento que contiene la regla, sin copiar su texto, para
   que no se desincronice. Las puertas del back salen de su propio
   `AGENTS.md` y `doc/`. En el front se descartaron, y no hay que volver a
   proponerlas, dos alternativas: sustituir `AGENTS.md`/`doc/` por la
   constitución, e importarla desde `CLAUDE.md`. Prepara la entrada para
   `/speckit-constitution` y dásela al usuario.
f. **Workflow**: las secciones "Spec-kit features" y "Language" del anexo A,
   si se aprobaron en la Fase 1, adaptadas al back. Por ejemplo, los ficheros
   de ignore que reescribe `/speckit-implement` serán los del stack del back.
g. **Memoria**: una memoria de tipo `project` que registre la adopción y lo
   que hay que comprobar tras cada actualización de spec-kit (modelo: anexo C,
   `project_speckit_trial.md`). En el back no hubo prueba, la adopción es
   directa: no copies la historia de la prueba.

Di qué se prueba y cómo, como pide tu workflow: estos cambios no tocan código
de la aplicación, así que justifica por qué no aplican tests unitarios ni e2e.

## Fase 4: verificación

Sugiere al usuario que abra una conversación nueva y ejecute
`/context-audit` (solo él puede lanzarla), para detectar duplicados entre la
memoria, el workflow y la constitución recién creados.

---

# Anexos (copias literales de los ficheros del front)

Excluidas por ser solo del front: las memorias `project_axios_vs_fetch.md`,
`project_primevue_status.md` y `feedback_offer_visual_examples.md` (ejemplos
HTML de opciones visuales), y las skills de Vue, Vite y Playwright.

## Anexo A: `doc/workflow.md` (completo; se carga en cada sesión con una línea `@doc/workflow.md` en `AGENTS.md`)

### doc/workflow.md

`````markdown
# Working agreements

How the user works with Claude on this project. Applies to every session.

## Confirmations

- Related changes may be grouped into one proposal, provided each change is
  listed explicitly so the approval covers each one.

## Learning mode

- The user is using this project to learn Vue.js while building it with
  Claude. This extends the "Proposals" section below, not a replacement for
  it.
- When proposing a change to project files (Vue components, tests, config,
  Docker), explain it didactically, as if it were a guided exercise: what the
  change does and which Vue concepts it involves (e.g. `ref` vs `reactive`,
  props, emits, Pinia stores, routes), in prose, without pasting the code
  itself.
- When proposing several options, explain each one didactically before the
  user picks (the user had no prior JavaScript/TypeScript/Vue experience, so
  bare option labels aren't enough): what it does, and what the official docs
  recommend about it — or say explicitly that they have no stance and it's a
  project decision.
- In each option or rule, compare the resulting code: which is simpler and
  which adds complexity that may be unnecessary (extra state, coordination,
  files touched). Rules are defaults, not dogma: state the condition that
  overrides one on simplicity grounds.
- When a decision comes from an installed skill, say which skill and why it
  recommends it.
- Invite questions, answer them, and apply the change only once the user says
  they have no more doubts.

### Known concepts

- The concepts listed below are ones the user already knows: name them
  without explaining them. Exception: when one shows up in a way the user
  hasn't seen yet (a new use, option or interaction with another API),
  explain only that new part.
- When the user says they already know a concept, propose adding it here;
  if they say they've forgotten one, propose removing it.
- List:
  - `ref`
  - `computed`
  - `v-if` / `v-else / v-else-if`
  - A composable returning functions the component uses as event handlers
  - `readonly` on a composable's returned state

## Proposals

- File changes: never show a diff or pasted content in chat, regardless of
  whether it's a new file, a small edit or a large rewrite. Explain what
  changed and why, listing every file affected, so the user knows what to
  review. The user reviews the actual change with `git diff` in VSCode.
- Design rationale: when a change is driven by a design pattern, a SOLID
  principle, an architecture decision or a code-quality improvement — however
  small, including a rename or extracting a method — split the explanation in
  two: what the change does (functional effect) and why this design
  specifically — what coupling, responsibility or extensibility problem it
  solves, or which principle it applies, and why a simpler alternative wasn't
  enough. Always explicit, even for small changes. A proposal without the
  second part is incomplete.
  - Revisit note: tried as "always explicit" starting 2026-10-08. After a few
    sessions, consider switching to "only for structural changes
    (interfaces, patterns, layers)" if "always" turns out noisy for trivial
    changes.
- When a decision is closed, record it as
  [AGENTS.md → Where documentation lives](../AGENTS.md#where-documentation-lives)
  says, and reply with a one-line summary.
- Testing: every proposal — the initial one or a later split into sequential
  blocks — must say what will be tested and how: which unit test(s)
  (new or updated, with file path) cover the change, and whether an e2e test
  applies (per [testing.md](testing.md)) or doesn't and why. State this
  alongside the files-affected list, not as an afterthought once the change
  is already applied.

## Steps and installs

- Design decisions: one at a time, each with its trade-offs and a
  recommendation.
- Installation and setup steps: give the commands as one copy-paste block,
  with a short note on what each does and a link to the official docs. The
  user runs them.
- Never run package installs (npm, editor extensions). Give the exact
  command and wait for the result, then verify it by reading files.
- When asked to split a plan into sequential blocks, reorder the explanations
  already given as-is into blocks — don't compress or re-summarize them into
  shorter bullets. The block order must follow a real dependency (install
  before code, model before service); if the plan has none (independent
  blocks, a circular dependency, a block needing decisions from later ones),
  say so and propose how to resolve it instead of forcing an order.

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
  the session's commit. Only for `/feature-session`; outside it the user
  commits, and Claude doesn't ask whether to commit.
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
  and "Close" rules, instead of adding
  rows to the existing table (converge only appends). The handoff is
  `/feature-session`, never `/speckit-implement`.
- Never run `/speckit-implement`: it runs every phase in one go and creates or
  rewrites ignore files (`.gitignore`, `.prettierignore`, ESLint ignores).
  Its skill, and the deprecated `speckit-taskstoissues`, carry
  `disable-model-invocation: true` so only the user can launch them and their
  descriptions stay out of every session's context. A spec-kit upgrade
  regenerates `.claude/skills/speckit-*`: after one, check that both still
  have it. Rejected: the Claude integration's `--no-model-invocation` option
  (survives upgrades, but makes every `speckit-*` skill user-only); revisit
  if the manual check gets missed after an upgrade.

## Subagents

- Pick the subagent's model based on how much judgment the task needs:
  - Cheap model (haiku): the task has a closed, verifiable outcome and no
    design decisions — searching for a file/symbol, reading and summarizing a
    log or long file, running a command and reporting its output, mechanical
    multi-file edits (rename, reformat), checking whether something exists.
  - Default/stronger model: the task requires architecture or design choices,
    debugging with a non-obvious cause, reviewing code for subtle bugs,
    writing real business logic, or touches anything hard to undo where a
    near-correct answer could lead to a costly wrong decision.
  - Rule of thumb: if the expected result can be stated as one closed,
    checkable sentence, the cheap model is enough; if the subagent has to
    resolve ambiguity or make a design call, use the default model.

## Language

- Chat with the user in Spanish. Project documentation (`doc/*.md`,
  `AGENTS.md`) in English.
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
`````

## Anexo B: secciones de `AGENTS.md` de las que depende el workflow

### AGENTS.md (extracto: Conflicts between sources, Where documentation lives)

`````markdown
## Conflicts between sources

When two sources disagree (two skills, a skill and this file, or either of them and the official docs), tell the user before acting: name both sources, quote what each says, and say which one you will follow and why.

## Where documentation lives

- `doc/*.md` and this file describe the generic mechanism and how to add a
  case (steps, rules, conventions), not an inventory of implementations.
  Don't copy lists the code already expresses (routes, stores, type fields,
  endpoints, the components in a folder) or literal config values (URLs,
  ports, env vars, tool options): say what they do and point to the file.
- When a decision is closed or changes, record it in the same change that
  applies it:
  - A mechanism that applies to several files: the `doc/*.md` whose trigger
    above covers it.
  - Something specific to one component, store, composable or module (why
    this value, what it deliberately doesn't do, which alternatives were
    rejected for it): a comment in that file — a `/** */` header at the top
    of the `<script setup>` or the `.ts` file when it's about the whole file,
    a one-line `//` next to the line when it's a local trap. Only write it
    when removing it would likely lead a future reader to "simplify" the code
    back into the problem it avoids.
- A single case never becomes a `doc/*.md` rule. When a second case appears,
  propose to the user whether to generalize it into `doc/` or keep a comment
  in each file.
- Record only what was decided: what won't be done isn't recorded, except a
  rejected alternative's one-sentence summary with the condition to revisit
  it. When a decision changes, rewrite the existing text instead of appending
  a note.

`````

## Anexo C: memoria del front (índice y memorias no específicas del front)

### MEMORY.md

`````markdown
- [Devcontainer has no Docker access](feedback_devcontainer_no_docker.md) — give user docker commands to run themselves, ask for output
- [Docs before vendor source](feedback_docs_before_vendor_source.md) — docs/issues before source, for understanding AND fixes; ask before source-derived fixes
- [Keep Prettier changes](feedback_keep_prettier_changes.md) — always keep npm run format's edits to unrelated files, just list what changed
- [PrimeVue integration status](project_primevue_status.md) — pinned v4 + docs mirror, dark-only theming, BaseButton, auth modal via provide/inject
- [Axios vs fetch decision](project_axios_vs_fetch.md) — keeping native fetch; migration stays cheap if endpoints only call client.ts
- [READMEs are not for Claude](feedback_readmes_not_for_claude.md) — never edit/propose README.md or docker/README.md; doc work scopes to AGENTS.md, CLAUDE.md, doc/**
- [doc/alternatives only on request](feedback_alternatives_folder_on_request.md) — don't read/review/add unless asked; no .md may link it
- [doc/ out of automatic checks](feedback_no_prettier_on_docs.md) — doc/ excluded from oxlint, ESLint, Prettier; never run tools on docs by hand
- [Learning PDFs](feedback_learning_pdfs.md) — lessons in doc/pdf/learning/NN-*.pdf, always update INDICE.md with concepts
- [Read workflow.md each session](feedback_read_workflow_md_each_session.md) — only doc/ file @-imported; verify it loaded, else Read it
- [spec-kit adopted](project_speckit_trial.md) — trial closed 2026-10-31, spec-kit is the feature flow; specs language revisit at next feature, constitution closed, post-upgrade checks
- [Offer visual examples](feedback_offer_visual_examples.md) — visual options: offer standalone file:// HTML examples per option (CDN, pinned versions), like doc/examples/modales/
`````

### feedback_read_workflow_md_each_session.md

`````markdown
---
name: feedback-read-workflow-md-each-session
description: doc/workflow.md auto-loads via a bare @doc/workflow.md line in AGENTS.md; verify it appears in context at session start, and if not, Read it manually
metadata:
  node_type: memory
  type: feedback
  modified: 2026-10-31T01:59:14.616Z
  originSessionId: c20e365a-6573-43e2-b78c-ded7fa544ad0
---

`doc/workflow.md` is the only `doc/` file `@`-imported (bare line in AGENTS.md); the other decision docs are on-demand via AGENTS.md triggers. `@path` is not evaluated inside backticks/code spans.

**Why:** the import failed silently twice (prose, then backtick-wrapped `@`) and a whole session ran without the working agreements.

**How to apply:** at session start, confirm workflow.md's content is in the project-instructions context; if not, Read it before substantive work and flag the broken import.
`````

### feedback_docs_before_vendor_source.md

`````markdown
---
name: feedback-docs-before-vendor-source
description: "Check official docs (and issue trackers/community threads) before reading or inferring from a library's vendored source, both to understand behavior and to find a fix; ask before falling back to source-derived solutions"
metadata:
  node_type: memory
  type: feedback
  originSessionId: 5f020258-8799-44f4-a9e8-f1991cb5a4e7
  modified: 2026-10-21T11:29:49.975Z
---

Before reading a library's source code under `vendor/` (or equivalent, e.g. `node_modules/`) — whether to understand its behavior or to work out a fix/workaround for a bug — exhaust documented sources first: official docs, and for a concrete bug, also search the library's GitHub issues/discussions for the exact problem (there may be a maintainer-confirmed pattern or known workaround).

**Why:** explicit user preference, reinforced 2026-10-20 after a BaseButton/PrimeVue `asChild` bug fix — documentation (and documented community discussion) is the authoritative, maintained source; source-code inference is a last resort, not a fallback to reach for automatically.

**How to apply:**
- Understanding behavior: check docs first, as before.
- **Fixing a bug or designing a solution**: check docs, then search the library's issue tracker/discussions for the specific problem, before reading vendored source to devise a fix yourself.
- If neither docs nor issues cover it, **ask the user before reading the related source code** to look for a solution there — don't silently fall back to source-derived inference for fixes. (Reading source to merely *confirm/diagnose* a root cause, as opposed to designing the fix, is more tolerated but still flag it as inference, not a documented guarantee.)
- When a solution does end up being source-derived, say so explicitly and note it's not covered by official docs.
- Related to `doc/dependencies.md`'s "Library docs" (docs/llms.txt over node_modules) and "Research order for bugs and solutions" sections.
`````

### feedback_devcontainer_no_docker.md

`````markdown
---
name: feedback-devcontainer-no-docker
description: "Bash tool runs inside the devcontainer, not the Docker host — docker/docker compose commands don't work from here"
metadata:
  node_type: memory
  type: feedback
  originSessionId: 5f020258-8799-44f4-a9e8-f1991cb5a4e7
  modified: 2026-10-18T16:41:08.621Z
---

The Bash tool runs inside the devcontainer, not on the Docker host. `docker`/`docker compose` commands do not work from inside it.

**Why:** explicit user clarification of the execution environment topology.

**How to apply:** If a task needs a docker-level command (rebuild, restart, exec into another service, inspect host-level state), give the user the exact command to run in their own terminal instead of running it yourself. If you need the result, ask the user to paste it back.
`````

### feedback_keep_prettier_changes.md

`````markdown
---
name: feedback-keep-prettier-changes
description: Always keep changes npm run format/prettier makes to unrelated files; just list which files changed
metadata:
  node_type: memory
  type: feedback
  originSessionId: 96749f22-735b-4bdb-9849-8aea09bbb7fa
  modified: 2026-10-19T17:52:40.150Z
---

When `npm run format` (Prettier) reformats a file that wasn't part of the current change's scope, keep the reformatting — don't revert it to keep the diff scoped.

**Why:** user explicitly said formatting changes should always be kept, confirmed after I reverted an unrelated Prettier reformat of `HeroCodePanel.vue` and asked whether to keep it.

**How to apply:** After running `npm run format` as part of verifying a change, if it touches files beyond the ones intentionally edited, leave those changes in place. Just tell the user which files Prettier touched (a short list), so they know what to expect in `git diff` — don't ask whether to keep them, and don't `git checkout` them back.
`````

### feedback_no_prettier_on_docs.md

`````markdown
---
name: feedback-no-prettier-on-docs
description: "doc/ is not code — keep it out of every automatic check (lint, Prettier); don't run tools on it by hand either"
metadata:
  node_type: memory
  type: feedback
  originSessionId: 77f3ca78-5a9c-4007-a856-13a94266acb6
  modified: 2026-10-22T12:05:35.552Z
---

`doc/` is excluded from every automatic check: oxlint (`ignorePatterns` in `.oxlintrc.json`), ESLint (`globalIgnores` in `eslint.config.ts`) and Prettier (`.prettierignore`). Don't run `prettier --check/--write` or linters on `doc/` (or AGENTS.md / CLAUDE.md) by hand when verifying a change, and keep the exclusion when touching those configs.

**Why:** user said "la carpeta doc no hace falta que la pases por el prettier" and then "deja fuera de cualquier comprobacion automatica (lint, prettier etc) lo que hay en la carpeta doc, no es codigo" (2026-10-22). Running `--write` on docs added rewrap noise to their `git diff` review.

**How to apply:** closing checks are the package.json scripts (lint, format, type-check, unit tests). If a new tool is added (e.g. a markdown linter, spellcheck), exclude `doc/` from it too. Related: [[feedback-keep-prettier-changes]].
`````

### feedback_readmes_not_for_claude.md

`````markdown
---
name: feedback-readmes-not-for-claude
description: "README.md and docker/README.md are the user's own notes — don't edit them or include them in doc cleanups/proposals"
metadata:
  node_type: memory
  type: feedback
  originSessionId: 77f3ca78-5a9c-4007-a856-13a94266acb6
  modified: 2026-10-22T11:18:07.014Z
---

Leave `README.md` and `docker/README.md` alone: don't propose changes to them, don't include them in documentation cleanups or audits.

**Why:** user said "ignora los dos README, no son para ti" (2026-10-22) when a doc-slimming batch proposed rewriting the create-vue template README and deleting docker/README.md. They are the user's own human-facing notes, not part of the agent docs (AGENTS.md, doc/*.md).

**How to apply:** when auditing or editing project docs, scope to AGENTS.md, CLAUDE.md and doc/**. Facts found in the READMEs (e.g. broken paths) can be mentioned only if the user asks.
`````

### feedback_alternatives_folder_on_request.md

`````markdown
---
name: feedback-alternatives-folder-on-request
description: "doc/alternatives/ (one file per rejected alternative) is only read, reviewed or added to when the user asks; no .md may reference it"
metadata:
  node_type: memory
  type: feedback
  originSessionId: 77f3ca78-5a9c-4007-a856-13a94266acb6
  modified: 2026-10-22T13:45:23.520Z
---

`doc/alternatives/<alternative-name>.md` holds rejected alternatives, one file per alternative, each with a context line pointing to the decision it relates to. Don't read, review or audit that folder unless the user asks, and never add a reference to it from any other `.md` (AGENTS.md, CLAUDE.md, doc/*.md).

**Why:** user set it up on 2026-10-22 during a doc-slimming pass so rejected options don't load into context or clutter the decision docs; they explicitly said "salvo que yo lo pida, no revises esa carpeta". Because no doc may link it, this memory is the only place that records the convention.

**How to apply:** when a task involves rejected alternatives, only move them there if the user asks for it. Related: [[feedback-readmes-not-for-claude]].

**File format** (so adding one doesn't require reading the existing files; user asked to record it 2026-10-22). English, short (~10–20 lines), no Prettier/lint run on it:
1. `# <Alternative name>` — kebab-case filename matching it (e.g. `hand-rolled-reactive-store.md`).
2. `Context: alternative to <the chosen option> in [<doc or file>](<relative link>).` — link points from `doc/alternatives/` to where the decision is recorded (a `../<doc>.md#section`, or `../../src/...` when the decision lives as a comment in a file).
3. One or two short paragraphs or a bullet list: when to revisit (the condition), and the alternative's pro and limit; cite an official doc link when one backs the choice, or say it's community opinion.
Two variants of the same alternative may share one file (done for `schema-based-form-validation.md`).
`````

### feedback_learning_pdfs.md

`````markdown
---
name: learning-pdfs
description: "Didactic PDF lessons go to doc/pdf/learning/ with a numbered name, and doc/pdf/learning/INDICE.md must be updated with every concept covered"
metadata:
  node_type: memory
  type: feedback
  originSessionId: 65eb7696-c2ea-4022-a24e-c44d277905f4
  modified: 2026-10-31T02:10:36.660Z
---

When the user asks for a didactic lesson/class PDF ("clase didáctica"), write it to `doc/pdf/learning/NN-tema.pdf` (sequential number) and add the PDF plus every concept it covers (with section number) to `doc/pdf/learning/INDICE.md`.

**Why:** the user wants one place to look up which PDF explains a concept when reviewing later.

**How to apply:** lesson structure the user asked for: concepts first with simple standalone examples, then escalate to how they apply in the project's real code. No exercises/solutions section (user asked to drop them, 2026-10-22). When the referenced code changes, regenerate the PDF (generator lives in the session scratchpad; rewrite if gone) and refresh INDICE.md. Content in Spanish (it's for the user, not project docs). First one: 01-typescript-en-client-ts.pdf; 02-validacion-formulario-registro.pdf (2026-10-30, block 3 of user-register; regenerated same day for the useForm refactor, now 28 sections — old scratchpads under /tmp/claude-1000/-home-ubuntu-app/*/scratchpad/ may still hold the generator, search there before rewriting). Both reportlab + DejaVu, same layout: cover with files/índice, Partes, yellow "En el código" boxes, blue "Idea clave" boxes, footer, chuleta annex. Related: doc/workflow.md → "Language".
`````

### project_speckit_trial.md

`````markdown
---
name: project-speckit-trial
description: spec-kit v1.2.5 adopted as the feature flow (trial on specs/001-confirm-email closed 2026-10-31); open revisits and what to check after a spec-kit upgrade
metadata:
  node_type: memory
  type: project
  originSessionId: d7ba490c-a33e-4bea-912e-9c8e297476db
  modified: 2026-10-31T18:51:45.610Z
---

Installed with `uvx ... specify init --here --integration claude --script sh` (pinned v1.2.5). Trial ran 2026-10-30/31 on branch confirmation-email-spec-kit (specs/001-confirm-email). On 2026-10-31 the user chose spec-kit over their own horizontal-blocks flow (preferred tasks grouped vertically by user story). The flow itself is in doc/workflow.md → "Spec-kit features" and `.claude/skills/feature-session`.

Open revisits (only when the user brings them up, or at the trigger stated):
- `specs/**` in Spanish: English would save ~2k tokens per `/feature-session`. Trigger: when the next spec-kit feature starts (before `/speckit-specify`), remind the user and ask; if they switch, follow the "Revisit" note in doc/workflow.md → "Language".
- `/speckit-converge` + Sessions-table rule (doc/workflow.md) not yet exercised in practice; check it works the first time converge appends tasks.

Constitution = index of plan gates pointing to AGENTS.md/doc, read only by the speckit skills. Closed 2026-10-31: neither replacing AGENTS.md/doc with it nor importing it from CLAUDE.md ("hybrid") — don't propose either again.

After any spec-kit upgrade, check overrides it can wipe: `disable-model-invocation: true` in `speckit-implement` and `speckit-taskstoissues`. `.specify/templates/overrides/` survives upgrades.

**Why:** the trial decided the flow; these are the leftovers the user may want to revisit.
**How to apply:** don't re-open the spec-kit vs. own-flow comparison; raise the revisits above only at their trigger.
`````

## Anexo D: skills

### D1. .claude/skills/feature-session/SKILL.md

`````markdown
---
name: "feature-session"
description: "Implement one session (a row of the Sessions table in tasks.md) of a spec-kit feature, following the project's learning-mode workflow."
argument-hint: "<feature dir, e.g. specs/001-confirm-email> <session id, e.g. S1>"
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

1. Follow `AGENTS.md` and `doc/workflow.md` to the letter (learning mode: didactic
   proposal, no diffs or pasted code in chat, list every file, functional effect + design
   rationale, say what is tested; apply only once the user says they have no more doubts;
   after applying, wait for the user's review). `doc/workflow.md` → "Spec-kit features"
   explains this flow.
2. Read `<feature dir>/tasks.md`. The "Sessions" tables (the main one and one per
   `Convergence` phase, if any) say which tasks belong to this session and how it closes. Check that every task of earlier sessions is marked `[X]`;
   if not, stop and tell the user which ones are missing.
3. Read `<feature dir>/spec.md` and `plan.md`, and from `research.md` only the decisions
   cited by this session's tasks. Read the `doc/*.md` files whose `AGENTS.md` trigger
   matches the work.
4. Implement only this session's tasks, one proposal per task or per closely related group
   (list each task ID covered). Do NOT run `/speckit-implement`.
5. Close as the "Sessions" table says: run each check, report each result, keep Prettier's
   edits to unrelated files and list them, and mark the finished tasks `[X]` in
   `tasks.md`.
6. Stop and wait for the user's review. Apply any changes they ask for and wait again.
   Once they say it's all OK, commit the session with the message format in
   `doc/workflow.md` → "Spec-kit features".
`````

### D2. .claude/skills/context-audit/SKILL.md

`````markdown
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

- Follow `AGENTS.md` and `doc/workflow.md` to the letter: learning-mode
  proposal, no diffs or pasted content in chat, every file listed, functional
  effect + design rationale, what gets tested. Change nothing until the user
  approves.
- Never edit or propose changes to `README.md` or `docker/README.md`.
- Never open `doc/alternatives/`: the script reports its size only.
- Don't edit the bodies of vendor skills (`speckit-*`, `vue-*`, `vite`,
  `playwright-cli`, ...): an upgrade overwrites them. Frontmatter overrides are
  fine only when `doc/workflow.md` records them.

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
   `${CLAUDE_SKILL_DIR}/scripts/measure.py --root . --size-only doc/alternatives`.
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
   one. Testing: these changes touch no `src/`, so say no unit or e2e test
   applies; `doc/` is outside lint and Prettier.
5. Wait. Apply only the approved items, record each closed decision where
   AGENTS.md → "Where documentation lives" says (and add a rejected one to
   "Closed decisions" above), then report what changed per file.
`````

### D2. .claude/skills/context-audit/scripts/measure.py

`````python
#!/usr/bin/env python3
"""Inventory the markdown that can enter Claude's context and estimate its cost.

Read-only: opens files, never writes, no network. Prints a markdown report.

Estimates, not measurements: the Claude tokenizer isn't public, so tokens are
characters / ratio (Spanish 3.3, English 4.0 chars per token). Translating
Spanish prose to English is assumed to shrink it ~10% in characters. Code
(fenced blocks and inline code) is counted at the English ratio and never
counted as translatable.

Load classes (only where a fixed rule decides them):
  always    - files reached through bare `@path` lines from CLAUDE.md (and
              ~/.claude/CLAUDE.md), the memory index MEMORY.md, and the
              frontmatter of skills without `disable-model-invocation: true`
  on-demand - files linked from an always-loaded file, memory files, skill
              bodies, .specify/ and specs/
  bundled   - a skill's extra .md files, read one at a time (average shown)
  rarely    - everything else (READMEs, doc/examples, ...)

Usage: measure.py --root <project dir> [--size-only <dir>]...
"""

import argparse
import os
import re
from pathlib import Path

ES_CHARS_PER_TOKEN = 3.3
EN_CHARS_PER_TOKEN = 4.0
EN_LENGTH_FACTOR = 0.9

# Words frequent in one language and absent from the other: "no", "a" or "me"
# would count for both and skew the share.
ES_WORDS = set(
    "el la los las del que y en un una por para con se es lo al como más pero"
    " sus su ya este esta cuando sin sobre también hay donde cada solo tras"
    " antes después todo hasta desde son está qué cómo".split()
)
EN_WORDS = set(
    "the of and to in is that for it with as on be this are by or not when"
    " from an at if each only before after all should use which can".split()
)

FENCE = re.compile(r"^```.*?^```", re.S | re.M)
INLINE_CODE = re.compile(r"`[^`\n]+`")
FRONTMATTER = re.compile(r"^---\n(.*?)\n---\n", re.S)
IMPORT_LINE = re.compile(r"^@(\S+)\s*$", re.M)
MD_LINK = re.compile(r"\]\(([^)#\s]+\.md)(?:#[^)]*)?\)")
WIKI_LINK = re.compile(r"\[\[([^\]]+)\]\]")


def split_code(text):
    code = FENCE.findall(text)
    prose = FENCE.sub("", text)
    code += INLINE_CODE.findall(prose)
    prose = INLINE_CODE.sub("", prose)
    return prose, sum(len(c) for c in code)


def spanish_share(prose):
    words = re.findall(r"[a-záéíóúñü]+", prose.lower())
    es = sum(w in ES_WORDS for w in words)
    en = sum(w in EN_WORDS for w in words)
    return es / (es + en) if es + en else 0.0


def estimate(text):
    prose, code_chars = split_code(text)
    share = spanish_share(prose)
    es_chars = len(prose) * share
    en_chars = len(prose) - es_chars + code_chars
    tokens = es_chars / ES_CHARS_PER_TOKEN + en_chars / EN_CHARS_PER_TOKEN
    as_english = es_chars * EN_LENGTH_FACTOR / EN_CHARS_PER_TOKEN
    saving = es_chars / ES_CHARS_PER_TOKEN - as_english
    return share, tokens, saving


def language(share):
    return "ES" if share >= 0.6 else "EN" if share <= 0.2 else "mixed"


def read(path):
    return path.read_text(encoding="utf-8")


def imports(path, seen):
    """Files pulled in by bare @path lines, recursively (not inside code)."""
    if path in seen or not path.is_file():
        return
    seen.add(path)
    for target in IMPORT_LINE.findall(FENCE.sub("", read(path))):
        imports((path.parent / os.path.expanduser(target)).resolve(), seen)


def memory_dir(root):
    slug = str(root).replace("/", "-")
    return Path.home() / ".claude" / "projects" / slug / "memory"


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--root", required=True)
    parser.add_argument("--size-only", action="append", default=[])
    args = parser.parse_args()
    root = Path(args.root).resolve()
    size_only = [(root / d).resolve() for d in args.size_only]
    mem = memory_dir(root)

    always = set()
    for entry in (root / "CLAUDE.md", Path.home() / ".claude" / "CLAUDE.md"):
        imports(entry.resolve(), always)
    if (mem / "MEMORY.md").is_file():
        always.add((mem / "MEMORY.md").resolve())

    linked = set()
    for path in always:
        for target in MD_LINK.findall(read(path)):
            linked.add((path.parent / target).resolve())

    rows = []

    def add(label, load, text):
        share, tokens, saving = estimate(text)
        rows.append((label, language(share), load, tokens, saving))

    def rel(path):
        path = path.resolve()
        if path.is_relative_to(root):
            return str(path.relative_to(root))
        if path.is_relative_to(mem):
            return "memory/" + str(path.relative_to(mem))
        return str(path).replace(str(Path.home()), "~")

    # Skills: frontmatter and body are loaded at different times.
    skill_files = set()
    for skill in sorted((root / ".claude" / "skills").glob("*/SKILL.md")):
        text = read(skill)
        match = FRONTMATTER.match(text)
        front = match.group(1) if match else ""
        name = skill.parent.name
        if re.search(r"^disable-model-invocation:\s*true", front, re.M):
            rows.append((f"skill {name} (frontmatter)", "-", "never (disabled)", 0, 0))
        else:
            add(f"skill {name} (frontmatter)", "always", front)
        add(f"skill {name} (body)", "on-demand", text[match.end() if match else 0 :])
        bundled = [p for p in skill.parent.rglob("*.md") if p != skill]
        if bundled:
            # Read one at a time when the body points to them: show the
            # average per file, not a sum that never loads together.
            share, tokens, saving = estimate("".join(read(p) for p in bundled))
            rows.append((
                f"skill {name} ({len(bundled)} bundled .md, avg per file)",
                language(share), "bundled", tokens / len(bundled), saving / len(bundled),
            ))
        skill_files.update(p.resolve() for p in skill.parent.rglob("*.md"))

    candidates = set(always) | linked
    for pattern in ("**/CLAUDE.md", "AGENTS.md", "*.md", "doc/**/*.md",
                    ".specify/**/*.md", "specs/**/*.md", "docker/*.md"):
        candidates.update(p.resolve() for p in root.glob(pattern)
                          if "node_modules" not in p.parts)
    if mem.is_dir():
        candidates.update(p.resolve() for p in mem.glob("*.md"))

    for path in sorted(candidates - skill_files):
        if not path.is_file():
            continue
        if path in always:
            load = "always"
        elif (path in linked or path.is_relative_to(mem)
              or rel(path).startswith((".specify/", "specs/"))):
            load = "on-demand"
        else:
            load = "rarely"
        if any(path.is_relative_to(d) for d in size_only):
            # Not opened on purpose: size only, at the Spanish ratio as an upper bound.
            tokens = path.stat().st_size / ES_CHARS_PER_TOKEN
            rows.append((rel(path) + " (size only)", "?", load, tokens, 0))
            continue
        add(rel(path), load, read(path))

    order = {"always": 0, "on-demand": 1, "bundled": 2, "rarely": 3, "never (disabled)": 4}
    rows.sort(key=lambda r: (order[r[2]], -r[3]))

    print("| File | Language | Load | Tokens (est.) | Saving if EN (est.) |")
    print("|---|---|---|---:|---:|")
    for label, lang, load, tokens, saving in rows:
        print(f"| {label} | {lang} | {load} | {tokens:,.0f} | {saving:,.0f} |")

    print("\n| Load | Files | Tokens (est.) | Saving if EN (est.) |")
    print("|---|---:|---:|---:|")
    for load in order:
        group = [r for r in rows if r[2] == load]
        if group:
            print(f"| {load} | {len(group)} | {sum(r[3] for r in group):,.0f}"
                  f" | {sum(r[4] for r in group):,.0f} |")

    # Memory links whose target no memory declares as its name or file stem.
    if mem.is_dir():
        names = set()
        for path in mem.glob("*.md"):
            names.add(path.stem)
            match = re.search(r"^name:\s*(\S+)", read(path), re.M)
            if match:
                names.add(match.group(1))
        broken = [
            (path.name, target)
            for path in sorted(mem.glob("*.md"))
            for target in WIKI_LINK.findall(read(path))
            if target not in names and target.replace("_", "-") not in names
        ]
        print("\nUnresolved memory links:")
        for source, target in broken:
            print(f"- {source} -> [[{target}]]")
        if not broken:
            print("- none")

    print(f"\nRatios: ES {ES_CHARS_PER_TOKEN}, EN {EN_CHARS_PER_TOKEN} chars/token;"
          f" ES->EN length x{EN_LENGTH_FACTOR}. Estimates only.")


if __name__ == "__main__":
    main()
`````

### D3. Frontmatter de .claude/skills/speckit-implement/SKILL.md (override; igual en speckit-taskstoissues)

`````markdown
---
name: "speckit-implement"
description: "Execute the implementation plan by processing and executing all tasks defined in tasks.md"
argument-hint: "Optional implementation guidance or task filter"
compatibility: "Requires spec-kit project structure with .specify/ directory"
metadata:
  author: "github-spec-kit"
  source: "templates/commands/implement.md"
user-invocable: true
# Project override: re-apply after a spec-kit upgrade (doc/workflow.md → Spec-kit features).
disable-model-invocation: true
---
`````

## Anexo E: plantilla de tareas

### .specify/templates/overrides/tasks-template.md

`````markdown
---

description: "Task list template for feature implementation"
---

<!--
  Project override of the core .specify/templates/tasks-template.md (spec-kit resolves
  .specify/templates/overrides/ first, and upgrades only replace the core file). Changes:
  tests required without TDD, no "commit after each task", and a Sessions table instead
  of the parallel-team strategy (one developer, one Claude Code session per row).
  Do not copy this comment into the generated tasks.md.
-->

# Tasks: [FEATURE NAME]

**Input**: Design documents from `/specs/[###-feature-name]/`

**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: REQUIRED by the constitution (principle IV) and listed in plan.md → Testing. TDD is not imposed: each test task goes in the same phase as the code it covers.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- **Single project**: `src/`, `tests/` at repository root
- **Web app**: `backend/src/`, `frontend/src/`
- **Mobile**: `api/src/`, `ios/src/` or `android/src/`
- Paths shown below assume single project - adjust based on plan.md structure

<!--
  ============================================================================
  IMPORTANT: The tasks below are SAMPLE TASKS for illustration purposes only.

  The /speckit-tasks command MUST replace these with actual tasks based on:
  - User stories from spec.md (with their priorities P1, P2, P3...)
  - Feature requirements from plan.md
  - Entities from data-model.md
  - Endpoints from contracts/

  Tasks MUST be organized by user story so each story can be:
  - Implemented independently
  - Tested independently
  - Delivered as an MVP increment

  DO NOT keep these sample tasks in the generated tasks.md file.
  ============================================================================
-->

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and basic structure

- [ ] T001 Create project structure per implementation plan
- [ ] T002 Initialize [language] project with [framework] dependencies
- [ ] T003 [P] Configure linting and formatting tools

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

Examples of foundational tasks (adjust based on your project):

- [ ] T004 Setup database schema and migrations framework
- [ ] T005 [P] Implement authentication/authorization framework
- [ ] T006 [P] Setup API routing and middleware structure
- [ ] T007 Create base models/entities that all stories depend on
- [ ] T008 Configure error handling and logging infrastructure
- [ ] T009 Setup environment configuration management

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - [Title] (Priority: P1) 🎯 MVP

**Goal**: [Brief description of what this story delivers]

**Independent Test**: [How to verify this story works on its own]

### Tests for User Story 1 (OPTIONAL - only if tests requested) ⚠️

> **NOTE**: Write each test alongside the code it covers, in this same phase.

- [ ] T010 [P] [US1] Contract test for [endpoint] in tests/contract/test_[name].py
- [ ] T011 [P] [US1] Integration test for [user journey] in tests/integration/test_[name].py

### Implementation for User Story 1

- [ ] T012 [P] [US1] Create [Entity1] model in src/models/[entity1].py
- [ ] T013 [P] [US1] Create [Entity2] model in src/models/[entity2].py
- [ ] T014 [US1] Implement [Service] in src/services/[service].py (depends on T012, T013)
- [ ] T015 [US1] Implement [endpoint/feature] in src/[location]/[file].py
- [ ] T016 [US1] Add validation and error handling
- [ ] T017 [US1] Add logging for user story 1 operations

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - [Title] (Priority: P2)

**Goal**: [Brief description of what this story delivers]

**Independent Test**: [How to verify this story works on its own]

### Tests for User Story 2 (OPTIONAL - only if tests requested) ⚠️

- [ ] T018 [P] [US2] Contract test for [endpoint] in tests/contract/test_[name].py
- [ ] T019 [P] [US2] Integration test for [user journey] in tests/integration/test_[name].py

### Implementation for User Story 2

- [ ] T020 [P] [US2] Create [Entity] model in src/models/[entity].py
- [ ] T021 [US2] Implement [Service] in src/services/[service].py
- [ ] T022 [US2] Implement [endpoint/feature] in src/[location]/[file].py
- [ ] T023 [US2] Integrate with User Story 1 components (if needed)

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently

---

## Phase 5: User Story 3 - [Title] (Priority: P3)

**Goal**: [Brief description of what this story delivers]

**Independent Test**: [How to verify this story works on its own]

### Tests for User Story 3 (OPTIONAL - only if tests requested) ⚠️

- [ ] T024 [P] [US3] Contract test for [endpoint] in tests/contract/test_[name].py
- [ ] T025 [P] [US3] Integration test for [user journey] in tests/integration/test_[name].py

### Implementation for User Story 3

- [ ] T026 [P] [US3] Create [Entity] model in src/models/[entity].py
- [ ] T027 [US3] Implement [Service] in src/services/[service].py
- [ ] T028 [US3] Implement [endpoint/feature] in src/[location]/[file].py

**Checkpoint**: All user stories should now be independently functional

---

[Add more user story phases as needed, following the same pattern]

---

## Phase N: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [ ] TXXX [P] Documentation updates in docs/
- [ ] TXXX Code cleanup and refactoring
- [ ] TXXX Performance optimization across all stories
- [ ] TXXX [P] Additional unit tests (if requested) in tests/unit/
- [ ] TXXX Security hardening
- [ ] TXXX Run quickstart.md validation

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3+)**: All depend on Foundational phase completion
  - User stories can then proceed in parallel (if staffed)
  - Or sequentially in priority order (P1 → P2 → P3)
- **Polish (Final Phase)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 2 (P2)**: Can start after Foundational (Phase 2) - May integrate with US1 but should be independently testable
- **User Story 3 (P3)**: Can start after Foundational (Phase 2) - May integrate with US1/US2 but should be independently testable

### Within Each User Story

- Each test lives in the same phase as the code it covers
- Models before services
- Services before endpoints
- Core implementation before integration
- Story complete before moving to next priority

### Parallel Opportunities

- All Setup tasks marked [P] can run in parallel
- All Foundational tasks marked [P] can run in parallel (within Phase 2)
- Once Foundational phase completes, all user stories can start in parallel (if team capacity allows)
- All tests for a user story marked [P] can run in parallel
- Models within a story marked [P] can run in parallel
- Different user stories can be worked on in parallel by different team members

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together (if tests requested):
Task: "Contract test for [endpoint] in tests/contract/test_[name].py"
Task: "Integration test for [user journey] in tests/integration/test_[name].py"

# Launch all models for User Story 1 together:
Task: "Create [Entity1] model in src/models/[entity1].py"
Task: "Create [Entity2] model in src/models/[entity2].py"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: Test User Story 1 independently
5. Deploy/demo if ready

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 → Test independently → Deploy/Demo (MVP!)
3. Add User Story 2 → Test independently → Deploy/Demo
4. Add User Story 3 → Test independently → Deploy/Demo
5. Each story adds value without breaking previous stories

### Sessions

<!--
  Group the tasks into sessions: each session is one new Claude Code conversation,
  run with `/feature-session <feature dir> <session id>` (see doc/workflow.md →
  Spec-kit features). Usually one session per phase; split a large phase and merge a
  tiny one (e.g. a one-task Setup) into the next. Every task belongs to exactly one
  session, in execution order. "Close" lists the checks that end the session, per
  plan.md → Testing and the constitution (principle V). "Command" is the exact text the
  user pastes into the new conversation, with this feature's real directory and the
  session id filled in.
-->

| Session | Tasks | Close | Command |
|---|---|---|---|
| S1 | T001–T00X | lint, format, type-check, Vitest | `/feature-session specs/[###-feature-name] S1` |
| S2 | ... | ... | ... |

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Mark each finished task `[X]` here; Claude commits each session once the user has reviewed it and says it's all OK
- Stop at any checkpoint to validate story independently
- Avoid: vague tasks, same file conflicts, cross-story dependencies that break independence
`````

## Anexo F: constitución del front (modelo de diseño, no para copiar)

### .specify/memory/constitution.md

`````markdown
# Space Trader Front Constitution

## Core Principles

Each principle is a gate that `/speckit-plan`'s "Constitution Check" MUST
verify. The rule's text and its rationale live in the linked document; if they
change there, this constitution stays untouched.

### I. Layers with one-way dependencies

The plan respects the layers and their dependency rules.
Source: `doc/architecture.md` (Layers, Rules per layer).

### II. The back's contract is the single source of truth

Types, errors and mocks come from the generated contract; nothing is invented
or hand-edited. Source: `doc/architecture.md` (api/, Back) and
`doc/testing.md`.

### III. The smallest structure that satisfies SOLID

Each new piece of state, composable or store is justified against a smaller
option. Source: `doc/conventions.md` (Design principles) and `AGENTS.md`
(Ask before generating).

### IV. Tested the way the user would use it (NON-NEGOTIABLE)

The plan says which unit and e2e tests cover each change, or why an e2e test
doesn't apply. Source: `doc/testing.md` and `doc/workflow.md` (Proposals).

### V. Quality checked by tools, without silencing errors

Lint, format and type-check clean, without silencing the type checker.
Source: `AGENTS.md` (Code style, Static analysis).

## Additional Constraints

Stack, libraries, styling and research order: `AGENTS.md`,
`doc/dependencies.md`, `doc/conventions.md` and `doc/ui.md`.

## Development Workflow

Proposals, learning mode, decisions and installs: `doc/workflow.md`.
Recording decisions: `AGENTS.md` (Where documentation lives).

## Governance

- `AGENTS.md` and `doc/*.md` are the source of truth. This constitution only
  lists the plan's gates and doesn't repeat their text, so it can't drift out
  of sync.
- If this constitution disagrees with `AGENTS.md`, `doc/*.md` or a skill, the
  user MUST be warned before acting, quoting both sources.
- Amendments: proposed to the user and applied only with their approval. It
  changes only when a gate is added, removed or redefined, not when a linked
  document's detail changes.
- Semantic versioning: MAJOR when a gate is removed or redefined; MINOR when
  one is added; PATCH for wording.
- Compliance: a violation in the plan is accepted only when justified in its
  "Complexity Tracking" section.

**Version**: 2.0.1 | **Ratified**: 2026-10-30 | **Last Amended**: 2026-10-31
`````
