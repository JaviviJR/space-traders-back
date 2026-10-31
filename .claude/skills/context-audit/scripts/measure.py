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
                          if not {"node_modules", "vendor"} & set(p.parts))
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
